<?php

use App\Models\Company;
use App\Models\PaymentPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\StripeWebhookService;

test('a stripe trial subscription is treated as the current paid plan', function () {
    $plan = PaymentPlan::factory()->create([
        'name' => 'professional',
        'display_name' => 'Professional Plan',
        'amount' => 60,
        'stripe_price_id' => 'price_trial_123',
        'limits' => ['jobs' => null, 'reports' => true],
    ]);
    $user = User::factory()->hrProfessional(Company::factory()->create()->id)->create([
        'stripe_customer_id' => 'cus_trial_123',
    ]);

    app(StripeWebhookService::class)->handle([
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_trial_123',
                'customer' => 'cus_trial_123',
                'status' => 'trialing',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addDays(60)->timestamp,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'trial_end' => now()->addDays(60)->timestamp,
                'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id],
                'items' => [
                    'data' => [
                        ['price' => ['id' => 'price_trial_123']],
                    ],
                ],
            ],
        ],
    ]);

    $user = $user->fresh();
    $subscription = $user->activeSubscription;

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe('trialing')
        ->and($subscription->payment_plan_id)->toBe($plan->id)
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->isTrial())->toBeTrue()
        ->and($user->tier)->toBe('professional')
        ->and($user->hasActiveSubscription())->toBeTrue();

    $currentPlan = app(PlanLimitService::class)->currentPlan($user);

    expect($currentPlan?->id)->toBe($plan->id)
        ->and($currentPlan?->display_name)->toBe('Professional Plan');
});

test('subscriptions page shows a trialing paid plan instead of free tier', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $freePlan = PaymentPlan::factory()->hrBasic()->create();
    $paidPlan = PaymentPlan::factory()->create([
        'name' => 'professional',
        'display_name' => 'Professional Plan',
        'amount' => 60,
        'target_role' => 'hr_professional',
        'limits' => ['jobs' => null, 'reports' => true],
    ]);

    Subscription::create([
        'user_id' => $hr->id,
        'payment_plan_id' => $freePlan->id,
        'status' => 'canceled',
        'starts_at' => now()->subDay(),
        'canceled_at' => now(),
    ]);

    Subscription::create([
        'user_id' => $hr->id,
        'payment_plan_id' => $paidPlan->id,
        'stripe_subscription_id' => 'sub_trial_ui',
        'status' => 'trialing',
        'starts_at' => now(),
        'ends_at' => now()->addDays(60),
        'trial_ends_at' => now()->addDays(60),
    ]);

    $this->actingAs($hr)
        ->get(route('subscriptions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/Subscriptions')
            ->where('activeSubscription.payment_plan.id', $paidPlan->id)
            ->where('activeSubscription.payment_plan.display_name', 'Professional Plan')
            ->where('activeSubscription.status', 'trialing')
        );

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.subscription_plan', 'Professional Plan')
            ->where('stats.subscription_status', 'trialing')
            ->where('auth.user.tier', 'professional')
            ->where('auth.user.subscription_tier', 'professional')
        );

    $this->actingAs($hr)
        ->get(route('reports'))
        ->assertOk();
});

test('trialing professional hr can post more than five jobs', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $plan = PaymentPlan::factory()->create([
        'target_role' => $hr->role,
        'amount' => 60,
        'name' => 'professional',
        'display_name' => 'Professional Plan',
        'limits' => ['jobs' => null, 'reports' => true],
    ]);
    Subscription::create([
        'user_id' => $hr->id,
        'payment_plan_id' => $plan->id,
        'status' => 'trialing',
        'starts_at' => now(),
        'ends_at' => now()->addDays(60),
        'trial_ends_at' => now()->addDays(60),
    ]);

    \App\Models\Job::factory()->count(5)->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload())
        ->assertRedirect(route('post-jobs'))
        ->assertSessionDoesntHaveErrors();

    expect(\App\Models\Job::visibleTo($hr)->count())->toBe(6);
});
