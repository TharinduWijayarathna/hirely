<?php

use App\Models\Company;
use App\Models\PaymentPlan;
use App\Models\User;

test('paid features are unlocked when payments are not required', function () {
    config(['payments.required' => false]);

    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($hr)
        ->get(route('reports'))
        ->assertOk();
});

test('paid checkout skips stripe when payments are not required', function () {
    config(['payments.required' => false]);

    $seeker = User::factory()->jobSeeker()->create();
    $plan = PaymentPlan::factory()->seekerPremium()->create();

    $this->actingAs($seeker)
        ->postJson(route('payment.checkout', $plan))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($seeker->fresh()->activeSubscription)
        ->not->toBeNull()
        ->and($seeker->fresh()->activeSubscription->payment_plan_id)->toBe($plan->id)
        ->and($seeker->fresh()->activeSubscription->stripe_subscription_id)->toBeNull();
});

test('billing portal is skipped when payments are not required', function () {
    config(['payments.required' => false]);

    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->get(route('payment.billing-portal'))
        ->assertRedirect(route('payments'))
        ->assertSessionHas('info');
});

test('shared inertia props include the payments required flag', function () {
    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payments.required', true)
        );
});
