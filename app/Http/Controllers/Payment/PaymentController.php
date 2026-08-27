<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display subscription plans (for HR) or payment options (for Job Seekers)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role;

        $plans = PaymentPlan::active()
            ->forRole($role)
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->get();

        $activeSubscription = $user->activeSubscription;
        if ($activeSubscription) {
            $activeSubscription->load('paymentPlan');
        }
        $subscriptions = $user->subscriptions()->with('paymentPlan')->latest()->get();

        return Inertia::render($role === 'hr_professional' ? 'hr/Subscriptions' : 'job-seeker/Payments', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
            'subscriptions' => $subscriptions,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create checkout session for subscription
     */
    public function checkout(Request $request, $plan)
    {
        // Handle route model binding manually
        if (is_numeric($plan)) {
            $plan = PaymentPlan::findOrFail($plan);
        } elseif (! ($plan instanceof PaymentPlan)) {
            return redirect()->back()->with('error', 'Payment plan not found.');
        }

        $user = $request->user();

        // Validate user can subscribe to this plan
        if ($plan->target_role !== 'both' && $plan->target_role !== $user->role) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'This plan is not available for your role.',
                ], 400);
            }

            return redirect()->back()->with('error', 'This plan is not available for your role.');
        }

        try {
            $activeSubscription = $user->activeSubscription;

            // Free plans, or any plan when the payment gateway is off, skip Stripe.
            if ($plan->amount == 0 || ! config('payments.required')) {
                if ($activeSubscription && $activeSubscription->payment_plan_id !== $plan->id) {
                    $activeSubscription->update([
                        'status' => 'canceled',
                        'canceled_at' => now(),
                    ]);
                }

                if (! $activeSubscription || $activeSubscription->payment_plan_id !== $plan->id) {
                    \App\Models\Subscription::create([
                        'user_id' => $user->id,
                        'payment_plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => $plan->interval === 'month' ? now()->addMonth() : now()->addYear(),
                    ]);
                }

                $message = $plan->amount == 0
                    ? 'Free subscription activated!'
                    : 'Subscription activated.';

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect_url' => route($user->billingRouteName()),
                    ]);
                }

                return redirect()->route($user->billingRouteName())->with('success', $message);
            }

            if ($activeSubscription && $activeSubscription->payment_plan && $activeSubscription->payment_plan->amount == 0 && $plan->amount > 0) {
                $activeSubscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);
            }

            // Create price if it doesn't exist for paid plans
            if (! $plan->stripe_price_id) {
                $price = $this->stripeService->createPriceForPlan($plan);
                if ($price) {
                    $plan->update(['stripe_price_id' => $price->id]);
                }
            }

            if (! $plan->stripe_price_id) {
                return redirect()->back()->with('error', 'Failed to create payment plan. Please try again.');
            }

            $successUrl = route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('payment.cancel');

            $session = $this->stripeService->createCheckoutSession(
                $user,
                $plan,
                $successUrl,
                $cancelUrl
            );

            // Return JSON response with checkout URL for Inertia/AJAX requests
            // Frontend will handle the redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'checkout_url' => $session->url,
                ]);
            }

            // For non-AJAX requests, redirect directly
            return redirect()->away($session->url);
        } catch (\Exception $e) {
            \Log::error('Checkout error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to create checkout session: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to create checkout session: '.$e->getMessage());
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $user = $request->user();

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Handle paid subscriptions
            if (! $sessionId) {
                return redirect()->route($user->billingRouteName())->with('error', 'Invalid payment session.');
            }

            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid' || $session->payment_status === 'no_payment_required') {
                // Create or update subscription record
                if ($session->mode === 'subscription' && $session->subscription) {
                    $subscriptionId = is_object($session->subscription) ? $session->subscription->id : $session->subscription;
                    $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionId);

                    // Find plan from metadata or subscription
                    $planId = $session->metadata->plan_id ?? null;
                    if (! $planId) {
                        // Try to find plan from price ID
                        $priceId = is_object($stripeSubscription->items->data[0]->price)
                            ? $stripeSubscription->items->data[0]->price->id
                            : $stripeSubscription->items->data[0]->price;
                        $plan = \App\Models\PaymentPlan::where('stripe_price_id', $priceId)->first();
                        $planId = $plan?->id;
                    }

                    $subscription = \App\Models\Subscription::updateOrCreate(
                        ['stripe_subscription_id' => $stripeSubscription->id],
                        [
                            'user_id' => $user->id,
                            'payment_plan_id' => $planId,
                            'stripe_customer_id' => $session->customer,
                            'status' => $stripeSubscription->status,
                            'starts_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start ?? $stripeSubscription->created ?? now()->timestamp),
                            'ends_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end ?? now()->addMonth()->timestamp),
                            'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                            'canceled_at' => $stripeSubscription->canceled_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
                            'stripe_metadata' => $stripeSubscription->metadata->toArray() ?? [],
                        ]
                    );

                    // Create payment record
                    \App\Models\Payment::create([
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'payment_plan_id' => $planId,
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'amount' => ($session->amount_total ?? 0) / 100,
                        'currency' => $session->currency ?? 'usd',
                        'status' => 'succeeded',
                        'type' => 'subscription',
                        'description' => 'Subscription payment',
                        'paid_at' => now(),
                    ]);
                }

                return redirect()->route($user->billingRouteName())->with('success', 'Payment successful! Your subscription is now active.');
            }

            return redirect()->route($user->billingRouteName())->with('error', 'Payment session found but payment status is not complete.');
        } catch (\Exception $e) {
            \Log::error('Payment success error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->route($user->billingRouteName())->with('error', 'Payment verification failed. Please contact support if payment was deducted.');
        }
    }

    /**
     * Handle canceled payment
     */
    public function cancel(Request $request)
    {
        return redirect()->route($request->user()->billingRouteName())->with('info', 'Payment was canceled.');
    }

    /**
     * Access billing portal
     */
    public function billingPortal(Request $request)
    {
        if (! config('payments.required')) {
            return redirect()->route($request->user()->billingRouteName())
                ->with('info', 'Payment gateway is disabled.');
        }

        $user = $request->user();

        try {
            $returnUrl = route($user->billingRouteName());
            $session = $this->stripeService->createBillingPortalSession($user, $returnUrl);

            // For external Stripe URLs, use redirect()->away() to bypass Inertia
            return redirect()->away($session->url);
        } catch (\Exception $e) {
            return redirect()->route($user->billingRouteName())->with('error', 'Failed to access billing portal: '.$e->getMessage());
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        try {
            if ($subscription->stripe_subscription_id) {
                $this->stripeService->cancelSubscription($subscription->stripe_subscription_id, false);
            }

            $subscription->update([
                'cancel_at_period_end' => true,
                'canceled_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Subscription will be canceled at the end of the current period.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel subscription: '.$e->getMessage());
        }
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(Request $request, Subscription $subscription)
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        try {
            if ($subscription->stripe_subscription_id) {
                $this->stripeService->resumeSubscription($subscription->stripe_subscription_id);
            }

            $subscription->update([
                'cancel_at_period_end' => false,
                'canceled_at' => null,
            ]);

            return redirect()->back()->with('success', 'Subscription resumed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to resume subscription: '.$e->getMessage());
        }
    }
}
