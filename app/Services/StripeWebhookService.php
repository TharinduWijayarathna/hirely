<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function handle(array $event): void
    {
        $type = $event['type'] ?? null;
        $object = $event['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object),
            'customer.subscription.updated',
            'customer.subscription.created' => $this->syncSubscription($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            'invoice.paid' => $this->handleInvoicePaid($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            default => Log::info('Unhandled Stripe webhook', ['type' => $type]),
        };
    }

    protected function handleCheckoutSessionCompleted(array $session): void
    {
        if (($session['mode'] ?? null) !== 'subscription') {
            return;
        }

        $subscriptionId = $session['subscription'] ?? null;
        if (! $subscriptionId) {
            return;
        }

        $user = $this->findUser($session);
        $planId = $session['metadata']['plan_id'] ?? null;

        if (! $user || ! $planId) {
            return;
        }

        $subscription = Subscription::updateOrCreate(
            ['stripe_subscription_id' => is_string($subscriptionId) ? $subscriptionId : null],
            [
                'user_id' => $user->id,
                'payment_plan_id' => $planId,
                'stripe_customer_id' => $this->customerId($session),
                'status' => 'active',
                'starts_at' => now(),
            ]
        );

        $paymentIntent = $session['payment_intent'] ?? null;
        if ($paymentIntent && $user) {
            Payment::updateOrCreate(
                ['stripe_payment_intent_id' => $paymentIntent],
                [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'payment_plan_id' => $planId,
                    'amount' => ($session['amount_total'] ?? 0) / 100,
                    'currency' => strtoupper($session['currency'] ?? 'usd'),
                    'status' => 'succeeded',
                    'type' => 'subscription',
                    'description' => 'Subscription checkout',
                    'paid_at' => now(),
                ]
            );
        }
    }

    protected function syncSubscription(array $stripeSubscription): void
    {
        $user = User::where('stripe_customer_id', $this->customerId($stripeSubscription))->first();
        $plan = $this->planFromSubscription($stripeSubscription);

        if (! $user && isset($stripeSubscription['metadata']['user_id'])) {
            $user = User::find($stripeSubscription['metadata']['user_id']);
        }

        if (! $user || empty($stripeSubscription['id'])) {
            Log::warning('Stripe subscription webhook missing user', [
                'subscription' => $stripeSubscription['id'] ?? null,
            ]);

            return;
        }

        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription['id']],
            [
                'user_id' => $user->id,
                'payment_plan_id' => $plan?->id ?? $stripeSubscription['metadata']['plan_id'] ?? null,
                'stripe_customer_id' => $this->customerId($stripeSubscription),
                'status' => $this->mapStatus($stripeSubscription['status'] ?? 'active'),
                'starts_at' => $this->timestamp($stripeSubscription['current_period_start'] ?? null) ?? now(),
                'ends_at' => $this->timestamp($stripeSubscription['current_period_end'] ?? null),
                'trial_ends_at' => $this->timestamp($stripeSubscription['trial_end'] ?? null),
                'cancel_at_period_end' => (bool) ($stripeSubscription['cancel_at_period_end'] ?? false),
                'canceled_at' => $this->timestamp($stripeSubscription['canceled_at'] ?? null),
                'stripe_metadata' => $stripeSubscription['metadata'] ?? [],
            ]
        );
    }

    protected function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        if (empty($stripeSubscription['id'])) {
            return;
        }

        Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancel_at_period_end' => false,
        ]);
    }

    protected function handleInvoicePaid(array $invoice): void
    {
        $user = User::where('stripe_customer_id', $this->customerId($invoice))->first();
        if (! $user) {
            return;
        }

        $subscription = null;
        $stripeSubscriptionId = $invoice['subscription'] ?? null;
        if ($stripeSubscriptionId) {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        }

        $lookup = [];
        if (! empty($invoice['id'])) {
            $lookup['stripe_invoice_id'] = $invoice['id'];
        } elseif (! empty($invoice['payment_intent'])) {
            $lookup['stripe_payment_intent_id'] = $invoice['payment_intent'];
        } else {
            return;
        }

        Payment::updateOrCreate($lookup, [
            'user_id' => $user->id,
            'subscription_id' => $subscription?->id,
            'payment_plan_id' => $subscription?->payment_plan_id,
            'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
            'stripe_invoice_id' => $invoice['id'] ?? null,
            'amount' => ($invoice['amount_paid'] ?? 0) / 100,
            'currency' => strtoupper($invoice['currency'] ?? 'usd'),
            'status' => 'succeeded',
            'type' => 'subscription',
            'description' => $invoice['billing_reason'] ?? 'Invoice payment',
            'paid_at' => $this->timestamp($invoice['status_transitions']['paid_at'] ?? null) ?? now(),
        ]);
    }

    protected function handleInvoicePaymentFailed(array $invoice): void
    {
        $stripeSubscriptionId = $invoice['subscription'] ?? null;
        if ($stripeSubscriptionId) {
            Subscription::where('stripe_subscription_id', $stripeSubscriptionId)
                ->update(['status' => 'past_due']);
        }

        $user = User::where('stripe_customer_id', $this->customerId($invoice))->first();
        if (! $user || empty($invoice['id'])) {
            return;
        }

        Payment::updateOrCreate(
            ['stripe_invoice_id' => $invoice['id']],
            [
                'user_id' => $user->id,
                'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
                'amount' => ($invoice['amount_due'] ?? 0) / 100,
                'currency' => strtoupper($invoice['currency'] ?? 'usd'),
                'status' => 'failed',
                'type' => 'subscription',
                'description' => 'Invoice payment failed',
            ]
        );
    }

    protected function findUser(array $session): ?User
    {
        $userId = $session['metadata']['user_id'] ?? null;
        if ($userId) {
            return User::find($userId);
        }

        $customerId = $this->customerId($session);
        if ($customerId) {
            return User::where('stripe_customer_id', $customerId)->first();
        }

        return null;
    }

    protected function userIdFromMetadata(array $session): ?int
    {
        $userId = $session['metadata']['user_id'] ?? null;

        return $userId ? (int) $userId : null;
    }

    protected function customerId(array $object): ?string
    {
        $customer = $object['customer'] ?? null;

        return is_string($customer) ? $customer : null;
    }

    protected function planFromSubscription(array $stripeSubscription): ?PaymentPlan
    {
        $priceId = $stripeSubscription['items']['data'][0]['price']['id']
            ?? $stripeSubscription['items']['data'][0]['price']
            ?? null;

        if (! is_string($priceId)) {
            return null;
        }

        return PaymentPlan::where('stripe_price_id', $priceId)->first();
    }

    protected function mapStatus(string $status): string
    {
        return in_array($status, ['active', 'canceled', 'past_due', 'unpaid', 'trialing', 'incomplete'], true)
            ? $status
            : 'active';
    }

    protected function timestamp(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value);
    }
}
