<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        if (!$tenant->is_active) {
            abort(403, 'Tenant account is inactive');
        }

        // Store tenant in app container for global access
        app()->instance('tenant', $tenant);

        // Set tenant context for queries
        config(['app.tenant_id' => $tenant->id]);

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        // Method 1: Resolve by UUID in route parameter
        if ($request->route('tenant')) {
            return Tenant::where('uuid', $request->route('tenant'))
                ->active()
                ->first();
        }

        // Method 2: Resolve by domain/subdomain
        $host = $request->getHost();

        // Check for exact domain match first
        $tenant = Tenant::byDomain($host)->active()->first();

        if ($tenant) {
            return $tenant;
        }

        // Method 3: Extract subdomain (e.g., acme.adpro.test -> acme)
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain && $subdomain !== 'www') {
            return Tenant::where('subdomain', $subdomain)
                ->active()
                ->first();
        }

        return null;
    }

    protected function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);

        // If host has more than 2 parts (e.g., subdomain.domain.com)
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
