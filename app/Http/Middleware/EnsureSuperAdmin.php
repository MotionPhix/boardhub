<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensure the authenticated user is a super admin (tenant_id === null)
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

        // Check if user is a super admin (no tenant_id)
        if (!$user->isSuperAdmin()) {
            abort(403, 'Access denied. Super admin privileges required.');
        }

        return $next($request);
    }
}
