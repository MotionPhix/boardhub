<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Activitylog\Facades\Activity;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has admin privileges
        if (!$user->hasAnyRole(['super-admin', 'admin', 'owner', 'manager'])) {
            Activity::log('Unauthorized admin access attempt')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'requested_url' => $request->fullUrl(),
                ]);

            abort(403, 'Unauthorized access to admin panel.');
        }

        // Check if account is active
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been suspended.');
        }

        // Log admin access
        Activity::log('Admin panel accessed')
            ->causedBy($user)
            ->withProperties([
                'ip_address' => $request->ip(),
                'requested_url' => $request->fullUrl(),
            ]);

        return $next($request);
    }
}