<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->role)->toBe('job_seeker')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('public registration cannot escalate to admin', function () {
    $this->post(route('register.store'), [
        'name' => 'Attacker',
        'email' => 'attacker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    expect(User::where('email', 'attacker@example.com')->first()->role)->toBe('job_seeker');
});
