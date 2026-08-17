<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

test('unverified users are sent to email verification instead of the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users without two factor are sent to two factor setup', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('two-factor.show'));
});

test('email verification then sends the user to two factor setup', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('two-factor.show'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('two factor setup is skipped when the feature is disabled', function () {
    config([
        'fortify.two_factor' => false,
        'fortify.features' => array_values(array_filter(
            config('fortify.features'),
            fn ($feature) => $feature !== Features::twoFactorAuthentication()
        )),
    ]);

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('login skips two factor challenge when the feature is disabled', function () {
    config([
        'fortify.two_factor' => false,
        'fortify.features' => array_values(array_filter(
            config('fortify.features'),
            fn ($feature) => $feature !== Features::twoFactorAuthentication()
        )),
    ]);

    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('unverified users can use the dashboard when email verification is disabled', function () {
    disableEmailVerification();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
