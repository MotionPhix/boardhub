<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantSessionService;
use Spatie\Activitylog\Facades\Activity;

class TenantAccess
{
    public function __construct(
        private TenantSessionService $tenantSessionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant'); // Get resolved tenant from container
        $user = $request->user();

        // Allow public access to certain tenant routes
        if (!$user && $this->isPublicRoute($request)) {
            return $next($request);
        }

        // If user is authenticated, check tenant access
        if ($user) {
            // Check if user can access this tenant
            if (!$this->tenantSessionService->canSwitchToTenant($user, $tenant->id)) {
                Activity::log('Unauthorized tenant access attempt')
                    ->causedBy($user)
                    ->withProperties([
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'requested_url' => $request->fullUrl(),
                    ]);

                abort(403, 'You do not have access to this tenant.');
            }

            // Switch to the tenant context if different from current
            $currentTenant = $this->tenantSessionService->getCurrentTenant();
            if (!$currentTenant || $currentTenant['id'] !== $tenant->id) {
                $this->tenantSessionService->switchToTenant($user, $tenant->id);
            }
        }

        // Check if tenant is active
        if (!$tenant->is_active) {
            if ($user && $user->hasRole('super-admin')) {
                // Super admin can access inactive tenants
                return $next($request);
            }

            abort(503, 'This tenant is currently unavailable.');
        }

        return $next($request);
    }

    /**
     * Check if the route allows public access.
     */
    private function isPublicRoute(Request $request): bool
    {
        return $request->routeIs([
            'tenant.home',
            'tenant.billboards.index',
            'tenant.billboards.show',
            'tenant.api.billboards.*',
        ]);
    }
}