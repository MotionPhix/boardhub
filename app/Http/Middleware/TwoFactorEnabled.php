<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Fortify\Features;

class TwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip 2FA check if feature is disabled globally
        if (!Features::enabled(Features::twoFactorAuthentication())) {
            return $next($request);
        }

        // Skip 2FA check if user is accessing 2FA setup routes
        if ($this->isAccessingTwoFactorRoutes($request)) {
            return $next($request);
        }

        // Check if user is accessing admin panel
        $isAdminRoute = $request->routeIs('admin.*');

        // Require 2FA for admin routes
        if ($isAdminRoute && !$user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.setup')
                ->with('warning', 'Two-factor authentication is required to access the admin panel.');
        }

        // Require 2FA for super-admin users everywhere
        if ($user->hasRole('super-admin') && !$user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'Two-factor authentication is required for super-admin accounts.');
        }

        return $next($request);
    }

    /**
     * Check if the user is accessing two-factor authentication routes.
     */
    private function isAccessingTwoFactorRoutes(Request $request): bool
    {
        return $request->routeIs([
            'two-factor.*',
            'profile.security',
            'logout',
        ]);
    }
}