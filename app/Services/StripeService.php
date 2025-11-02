<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Subscription;
use App\Models\User;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        if (!$stripeSecret) {
            throw new \Exception('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
        }
        \Stripe\Stripe::setApiKey($stripeSecret);
        $this->stripe = new StripeClient($stripeSecret);
    }

    /**
     * Create or retrieve a Stripe customer
     */
    public function getOrCreateCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Create Stripe price for a plan
     * Returns null for free plans (they don't need Stripe prices)
     */
    public function createPriceForPlan(PaymentPlan $plan): ?\Stripe\Price
    {
        // Create product if needed
        $product = $this->stripe->products->create([
            'name' => $plan->display_name,
            'description' => $plan->description,
        ]);

        // Create price - Stripe doesn't support $0 recurring subscriptions
        // For free plans, we'll handle them directly in the app without Stripe
        if ($plan->amount == 0) {
            // Return null - free plans won't use Stripe
            return null;
        }

        $priceData = [
            'product' => $product->id,
            'unit_amount' => (int) ($plan->amount * 100),
            'currency' => strtolower($plan->currency),
            'recurring' => $plan->interval === 'month' ? ['interval' => 'month'] : ['interval' => 'year'],
        ];

        $price = $this->stripe->prices->create($priceData);

        // Update plan with product and price IDs
        $plan->update([
            'stripe_product_id' => $product->id,
            'stripe_price_id' => $price->id,
        ]);

        return $price;
    }

    /**
     * Create a checkout session for subscription
     */
    public function createCheckoutSession(User $user, PaymentPlan $plan, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        if ($plan->amount == 0) {
            throw new \Exception('Free plans should be handled directly without Stripe checkout.');
        }

        $customerId = $this->getOrCreateCustomer($user);

        $sessionData = [
            'customer' => $customerId,
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
            ],
        ];

        return $this->stripe->checkout->sessions->create($sessionData);
    }

    /**
     * Create a checkout session for one-time payment
     */
    public function createOneTimeCheckoutSession(User $user, float $amount, string $description, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        $customerId = $this->getOrCreateCustomer($user);

        return $this->stripe->checkout->sessions->create([
            'customer' => $customerId,
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $description,
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => $user->id,
                'type' => 'one_time',
            ],
        ]);
    }

    /**
     * Create a billing portal session
     */
    public function createBillingPortalSession(User $user, string $returnUrl): \Stripe\BillingPortal\Session
    {
        if (! $user->stripe_customer_id) {
            throw new \Exception('User does not have a Stripe customer ID');
        }

        return $this->stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * Retrieve a subscription from Stripe
     */
    public function getSubscription(string $subscriptionId): \Stripe\Subscription
    {
        return $this->stripe->subscriptions->retrieve($subscriptionId);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately = false): \Stripe\Subscription
    {
        if ($immediately) {
            return $this->stripe->subscriptions->cancel($subscriptionId);
        }

        return $this->stripe->subscriptions->update($subscriptionId, [
            'cancel_at_period_end' => true,
        ]);
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(string $subscriptionId): \Stripe\Subscription
    {
        return $this->stripe->subscriptions->update($subscriptionId, [
            'cancel_at_period_end' => false,
        ]);
    }

    /**
     * Sync subscription from Stripe to database
     */
    public function syncSubscription(\Stripe\Subscription $stripeSubscription): Subscription
    {
        $user = User::where('stripe_customer_id', $stripeSubscription->customer)->first();
        
        if (! $user) {
            throw new \Exception('User not found for Stripe customer ID: ' . $stripeSubscription->customer);
        }

        $plan = PaymentPlan::where('stripe_price_id', $stripeSubscription->items->data[0]->price->id)->first();

        $subscription = Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'user_id' => $user->id,
                'payment_plan_id' => $plan?->id,
                'stripe_customer_id' => $stripeSubscription->customer,
                'status' => $stripeSubscription->status,
                'starts_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'ends_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                'canceled_at' => $stripeSubscription->canceled_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
                'stripe_metadata' => $stripeSubscription->metadata->toArray(),
            ]
        );

        return $subscription;
    }
}

