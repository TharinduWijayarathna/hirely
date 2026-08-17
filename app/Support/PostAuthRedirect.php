<?php

namespace App\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;

class PostAuthRedirect
{
    /**
     * Send the user to email verification, 2FA setup, or the dashboard.
     */
    public static function to(Request $request, mixed $user, bool $confirmPassword = false, bool $verified = false): RedirectResponse
    {
        if ($confirmPassword && $request->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', time());
        }

        $location = self::location($user, $verified);

        if (self::mustVerifyEmail($user) || self::mustSetupTwoFactor($user)) {
            return redirect()->to($location);
        }

        return redirect()->intended($location);
    }

    public static function location(mixed $user, bool $verified = false): string
    {
        if (self::mustVerifyEmail($user)) {
            return route('verification.notice');
        }

        if (self::mustSetupTwoFactor($user)) {
            return route('two-factor.show');
        }

        $dashboard = route('dashboard', absolute: false);

        return $verified ? $dashboard.'?verified=1' : $dashboard;
    }

    public static function mustVerifyEmail(mixed $user): bool
    {
        return self::emailVerificationEnabled()
            && $user instanceof MustVerifyEmail
            && ! $user->hasVerifiedEmail();
    }

    public static function emailVerificationEnabled(): bool
    {
        return (bool) config('fortify.email_verification', true)
            && Features::enabled(Features::emailVerification());
    }

    public static function twoFactorEnabled(): bool
    {
        return (bool) config('fortify.two_factor', true)
            && Features::enabled(Features::twoFactorAuthentication());
    }

    public static function mustSetupTwoFactor(mixed $user): bool
    {
        return self::twoFactorEnabled()
            && is_object($user)
            && method_exists($user, 'hasEnabledTwoFactorAuthentication')
            && ! $user->hasEnabledTwoFactorAuthentication();
    }
}
