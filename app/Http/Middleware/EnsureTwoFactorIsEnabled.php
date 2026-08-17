<?php

namespace App\Http\Middleware;

use App\Support\PostAuthRedirect;
use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! Features::enabled(Features::twoFactorAuthentication())) {
            return $next($request);
        }

        if (PostAuthRedirect::mustVerifyEmail($user) || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        return redirect()->route('two-factor.show');
    }

    protected function shouldSkip(Request $request): bool
    {
        return $request->routeIs(
            'two-factor.*',
            'password.confirm',
            'password.confirm.store',
            'password.confirmation',
            'logout',
            'verification.*',
        );
    }
}
