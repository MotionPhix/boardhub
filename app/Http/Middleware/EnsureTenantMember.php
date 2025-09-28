<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMember
{
    /**
     * Handle an incoming request.
     *
     * Ensure the authenticated user has member access to the current tenant
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

        // Get the tenant from the request
        $tenant = $request->route('tenant');

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // Check if user has access to this tenant
        $membership = $user->getMembershipFor($tenant->id);

        if (!$membership || !$membership->isActive()) {
            abort(403, 'Access denied. You do not have access to this organization.');
        }

        // Any active membership level is sufficient for member routes
        // (Viewer, Member, Manager, Admin, Owner)

        return $next($request);
    }
}
