<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class TenantSessionService
{
    private const ACCESSIBLE_TENANTS_KEY = 'accessible_tenants';
    private const CURRENT_TENANT_KEY = 'current_tenant_id';
    private const DEVICE_TENANTS_KEY = 'device_logged_tenants';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Initialize session for user with their accessible tenants
     */
    public function initializeUserSession(User $user): void
    {
        $accessibleTenants = $this->getAccessibleTenants($user);

        Session::put(self::ACCESSIBLE_TENANTS_KEY, $accessibleTenants);

        // Initialize device-specific logged tenants
        $deviceTenants = Session::get(self::DEVICE_TENANTS_KEY, []);
        Session::put(self::DEVICE_TENANTS_KEY, $deviceTenants);

        // Set current tenant if user has access to only one tenant
        if (count($accessibleTenants) === 1) {
            $this->switchToTenant($user, $accessibleTenants[0]['id']);
        }
    }

    /**
     * Get user's accessible tenants (synced across all sessions)
     */
    public function getAccessibleTenants(User $user): array
    {
        $cacheKey = "user_{$user->id}_accessible_tenants";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $tenants = [];

            // Legacy: Direct tenant assignment
            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant && $tenant->is_active) {
                    $tenants[] = [
                        'id' => $tenant->id,
                        'uuid' => $tenant->uuid,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'role' => 'owner', // Legacy users are considered owners
                        'role_display' => 'Owner',
                        'logo_url' => $tenant->logo_url,
                        'primary_color' => $tenant->primary_color,
                        'membership_type' => 'legacy',
                        'last_accessed' => null,
                    ];
                }
            }

            // New: Membership-based access
            $memberships = $user->activeMemberships()
                ->with('tenant')
                ->get();

            foreach ($memberships as $membership) {
                if ($membership->tenant->is_active) {
                    $tenants[] = [
                        'id' => $membership->tenant->id,
                        'uuid' => $membership->tenant->uuid,
                        'name' => $membership->tenant->name,
                        'slug' => $membership->tenant->slug,
                        'role' => $membership->role,
                        'role_display' => $membership->getRoleDisplayName(),
                        'logo_url' => $membership->tenant->logo_url,
                        'primary_color' => $membership->tenant->primary_color,
                        'membership_type' => 'membership',
                        'last_accessed' => $membership->last_accessed_at?->toISOString(),
                        'joined_at' => $membership->joined_at?->toISOString(),
                        'permissions' => $membership->permissions ?? [],
                    ];
                }
            }

            // Sort by last accessed (most recent first), then by name
            usort($tenants, function ($a, $b) {
                if ($a['last_accessed'] && $b['last_accessed']) {
                    return strcmp($b['last_accessed'], $a['last_accessed']);
                }
                if ($a['last_accessed']) return -1;
                if ($b['last_accessed']) return 1;
                return strcmp($a['name'], $b['name']);
            });

            return $tenants;
        });
    }

    /**
     * Switch user to specific tenant
     */
    public function switchToTenant(User $user, int $tenantId): bool
    {
        $accessibleTenants = $this->getAccessibleTenants($user);
        $targetTenant = collect($accessibleTenants)->firstWhere('id', $tenantId);

        if (!$targetTenant) {
            return false;
        }

        // Set current tenant in session
        Session::put(self::CURRENT_TENANT_KEY, $tenantId);

        // Add to device-specific logged tenants
        $deviceTenants = Session::get(self::DEVICE_TENANTS_KEY, []);
        if (!in_array($tenantId, $deviceTenants)) {
            $deviceTenants[] = $tenantId;
            Session::put(self::DEVICE_TENANTS_KEY, $deviceTenants);
        }

        // Update last accessed time
        if ($targetTenant['membership_type'] === 'membership') {
            $membership = $user->getMembershipFor($tenantId);
            $membership?->updateLastAccess();
        }

        // Clear cached accessible tenants to refresh access time
        $this->clearUserCache($user);

        return true;
    }

    /**
     * Get current tenant for user session
     */
    public function getCurrentTenant(): ?array
    {
        $tenantId = Session::get(self::CURRENT_TENANT_KEY);
        if (!$tenantId) {
            return null;
        }

        $accessibleTenants = Session::get(self::ACCESSIBLE_TENANTS_KEY, []);
        return collect($accessibleTenants)->firstWhere('id', $tenantId);
    }

    /**
     * Get tenants logged into on this device
     */
    public function getDeviceLoggedTenants(): array
    {
        $deviceTenantIds = Session::get(self::DEVICE_TENANTS_KEY, []);
        $accessibleTenants = Session::get(self::ACCESSIBLE_TENANTS_KEY, []);

        return collect($accessibleTenants)
            ->whereIn('id', $deviceTenantIds)
            ->values()
            ->toArray();
    }

    /**
     * Remove tenant from device session
     */
    public function logoutFromTenant(int $tenantId): void
    {
        $deviceTenants = Session::get(self::DEVICE_TENANTS_KEY, []);
        $deviceTenants = array_values(array_filter($deviceTenants, fn($id) => $id !== $tenantId));
        Session::put(self::DEVICE_TENANTS_KEY, $deviceTenants);

        // If this was the current tenant, clear current tenant
        if (Session::get(self::CURRENT_TENANT_KEY) === $tenantId) {
            Session::forget(self::CURRENT_TENANT_KEY);
        }
    }

    /**
     * Clear all tenant sessions for user
     */
    public function clearAllTenantSessions(): void
    {
        Session::forget(self::ACCESSIBLE_TENANTS_KEY);
        Session::forget(self::CURRENT_TENANT_KEY);
        Session::forget(self::DEVICE_TENANTS_KEY);
    }

    /**
     * Refresh user's accessible tenants (call when memberships change)
     */
    public function refreshAccessibleTenants(User $user): void
    {
        $this->clearUserCache($user);
        $this->initializeUserSession($user);
    }

    /**
     * Check if user can switch to specific tenant
     */
    public function canSwitchToTenant(User $user, int $tenantId): bool
    {
        $accessibleTenants = $this->getAccessibleTenants($user);
        return collect($accessibleTenants)->contains('id', $tenantId);
    }

    /**
     * Get tenant switching analytics
     */
    public function getTenantSwitchingAnalytics(User $user): array
    {
        $accessibleTenants = $this->getAccessibleTenants($user);
        $deviceTenants = $this->getDeviceLoggedTenants();
        $currentTenant = $this->getCurrentTenant();

        return [
            'total_accessible' => count($accessibleTenants),
            'device_logged_count' => count($deviceTenants),
            'has_current_tenant' => $currentTenant !== null,
            'current_tenant_id' => $currentTenant['id'] ?? null,
            'can_switch' => count($accessibleTenants) > 1,
            'membership_types' => collect($accessibleTenants)
                ->countBy('membership_type')
                ->toArray(),
            'roles_distribution' => collect($accessibleTenants)
                ->countBy('role')
                ->toArray(),
        ];
    }

    /**
     * Handle tenant invitation acceptance
     */
    public function acceptInvitation(string $invitationToken): bool
    {
        $membership = Membership::where('invitation_token', $invitationToken)
            ->where('status', Membership::STATUS_PENDING)
            ->first();

        if (!$membership) {
            return false;
        }

        $membership->activate();

        // Refresh user's accessible tenants
        $this->refreshAccessibleTenants($membership->user);

        return true;
    }

    /**
     * Create tenant switching breadcrumbs
     */
    public function getTenantBreadcrumbs(): array
    {
        $currentTenant = $this->getCurrentTenant();
        $deviceTenants = $this->getDeviceLoggedTenants();

        if (!$currentTenant) {
            return [];
        }

        return [
            'current' => $currentTenant,
            'recent' => collect($deviceTenants)
                ->where('id', '!=', $currentTenant['id'])
                ->take(3)
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Clear user-specific cache
     */
    private function clearUserCache(User $user): void
    {
        Cache::forget("user_{$user->id}_accessible_tenants");
    }

    /**
     * Validate session integrity
     */
    public function validateSessionIntegrity(User $user): bool
    {
        $currentTenantId = Session::get(self::CURRENT_TENANT_KEY);

        if (!$currentTenantId) {
            return true; // No current tenant set is valid
        }

        return $this->canSwitchToTenant($user, $currentTenantId);
    }

    /**
     * Get tenant context for API responses
     */
    public function getTenantContext(): array
    {
        $currentTenant = $this->getCurrentTenant();

        if (!$currentTenant) {
            return [];
        }

        return [
            'tenant_id' => $currentTenant['id'],
            'tenant_uuid' => $currentTenant['uuid'],
            'tenant_name' => $currentTenant['name'],
            'user_role' => $currentTenant['role'],
            'permissions' => $currentTenant['permissions'] ?? [],
        ];
    }
}