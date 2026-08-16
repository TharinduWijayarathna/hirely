<?php

use App\Models\PaymentPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeWebhookService;

test('stripe webhook rejects invalid signatures', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $this->postJson(route('stripe.webhook'), ['type' => 'ping'])
        ->assertStatus(400);
});

test('subscription updated webhook syncs local subscription state', function () {
    $plan = PaymentPlan::factory()->create([
        'stripe_price_id' => 'price_test_123',
    ]);
    $user = User::factory()->hrProfessional()->create([
        'stripe_customer_id' => 'cus_test_123',
    ]);

    app(StripeWebhookService::class)->handle([
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_test_123',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'trial_end' => null,
                'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id],
                'items' => [
                    'data' => [
                        ['price' => ['id' => 'price_test_123']],
                    ],
                ],
            ],
        ],
    ]);

    $subscription = Subscription::where('stripe_subscription_id', 'sub_test_123')->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->payment_plan_id)->toBe($plan->id)
        ->and($subscription->status)->toBe('active');
});

test('subscription deleted webhook marks the subscription canceled', function () {
    $plan = PaymentPlan::factory()->create();
    $user = User::factory()->hrProfessional()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'payment_plan_id' => $plan->id,
        'stripe_subscription_id' => 'sub_cancel_me',
        'status' => 'active',
        'starts_at' => now(),
    ]);

    app(StripeWebhookService::class)->handle([
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_cancel_me',
            ],
        ],
    ]);

    expect($subscription->fresh()->status)->toBe('canceled');
});
