<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function jobListingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Backend Engineer',
        'description' => 'Build and maintain APIs.',
        'type' => 'full_time',
        'remote' => 'hybrid',
        'status' => 'active',
    ], $overrides);
}

function subscribeToPlan(\App\Models\User $user, array $limits, array $plan = []): \App\Models\Subscription
{
    $paymentPlan = \App\Models\PaymentPlan::factory()->create(array_merge([
        'target_role' => $user->role,
        'amount' => 49,
        'name' => 'professional',
        'display_name' => 'Professional Plan',
        'limits' => $limits,
    ], $plan));

    return \App\Models\Subscription::create([
        'user_id' => $user->id,
        'payment_plan_id' => $paymentPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
}

function disableEmailVerification(): void
{
    config([
        'fortify.email_verification' => false,
        'fortify.features' => array_values(array_filter(
            config('fortify.features'),
            fn ($feature) => $feature !== \Laravel\Fortify\Features::emailVerification()
        )),
    ]);
}
