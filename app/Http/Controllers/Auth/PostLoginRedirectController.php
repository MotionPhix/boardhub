<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PostLoginRedirectController extends Controller
{
    /**
     * Determine where to redirect user after successful login
     */
    public function handlePostLoginRedirect(User $user)
    {
        // 1. Super Admin (no tenant_id) -> System Dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('system.dashboard');
        }

        // 2. Get user's active memberships
        $activeMemberships = $user->activeMemberships()->with('tenant')->get();

        // 3. No active memberships -> Tenant selection
        if ($activeMemberships->isEmpty()) {
            return redirect()->route('tenants.select')
                ->with('message', 'Please select an organization to continue.');
        }

        // 4. Single membership -> Direct to appropriate dashboard
        if ($activeMemberships->count() === 1) {
            $membership = $activeMemberships->first();
            return $this->redirectToTenantDashboard($membership);
        }

        // 5. Multiple memberships -> Tenant selection
        return redirect()->route('tenants.select');
    }

    /**
     * Redirect to appropriate tenant dashboard based on role
     */
    private function redirectToTenantDashboard($membership)
    {
        $tenant = $membership->tenant;
        $role = $membership->role;

        // Redirect to proper tenant dashboard using UUID
        return redirect()->route('tenant.dashboard', ['tenant' => $tenant->uuid])
            ->with('message', "Welcome! You are logged in as {$role} for {$tenant->name}.");
    }

    /**
     * Get redirect URL for a specific role and tenant
     */
    public static function getRedirectUrl(User $user, $tenantId = null): string
    {
        // Super Admin
        if ($user->isSuperAdmin()) {
            return route('system.dashboard');
        }

        // If tenant specified, get role for that tenant
        if ($tenantId) {
            $membership = $user->getMembershipFor($tenantId);
            if ($membership && $membership->isActive()) {
                $tenant = $membership->tenant;
                return route('tenant.dashboard', ['tenant' => $tenant->uuid]);
            }
        }

        // Default: tenant selection
        return route('tenants.select');
    }

    /**
     * Handle tenant switching
     */
    public function switchTenant(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id'
        ]);

        $user = $request->user();
        $tenantId = $validated['tenant_id'];

        // Verify user has access to this tenant
        $membership = $user->getMembershipFor($tenantId);

        if (!$membership || !$membership->isActive()) {
            return back()->withErrors(['tenant_id' => 'You do not have access to this organization.']);
        }

        // Update last access time
        $membership->updateLastAccess();

        // Store current tenant in session for quick access
        session(['current_tenant_id' => $tenantId]);

        // Redirect to appropriate dashboard
        return $this->redirectToTenantDashboard($membership);
    }
}