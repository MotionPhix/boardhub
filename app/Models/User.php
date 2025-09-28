<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToTenant, HasRoles, HasApiTokens;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_active',
        'phone',
        'avatar',
        'position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships for multi-tenant membership
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->active();
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'memberships')
            ->withPivot(['role', 'status', 'permissions', 'joined_at', 'last_accessed_at'])
            ->withTimestamps();
    }

    public function accessibleTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('status', Membership::STATUS_ACTIVE);
    }

    // Enhanced admin access control for custom admin panel
    public function canAccessAdminPanel(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'owner', 'manager']);
    }

    public function isSuperAdmin(): bool
    {
        // Super admins must have no tenant_id (system-wide access only)
        return $this->tenant_id === null;
    }

    // Multi-tenant helper methods
    public function hasActiveMembershipFor(int $tenantId): bool
    {
        return $this->activeMemberships()
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    public function getMembershipFor(int $tenantId): ?Membership
    {
        return $this->memberships()
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function getRoleForTenant(int $tenantId): ?string
    {
        $membership = $this->getMembershipFor($tenantId);
        return $membership?->role;
    }

    public function hasRoleInTenant(string $role, int $tenantId): bool
    {
        return $this->getRoleForTenant($tenantId) === $role;
    }

    public function canManageTenant(int $tenantId): bool
    {
        $membership = $this->getMembershipFor($tenantId);
        return $membership?->canManage() ?? false;
    }

    public function isOwnerOfTenant(int $tenantId): bool
    {
        $membership = $this->getMembershipFor($tenantId);
        return $membership?->isOwner() ?? false;
    }

    public function switchToTenant(int $tenantId): bool
    {
        if (!$this->hasActiveMembershipFor($tenantId)) {
            return false;
        }

        // Update last accessed time
        $membership = $this->getMembershipFor($tenantId);
        $membership?->updateLastAccess();

        return true;
    }

    public function joinTenant(int $tenantId, string $role = Membership::ROLE_MEMBER, ?int $invitedBy = null): Membership
    {
        return $this->memberships()->create([
            'tenant_id' => $tenantId,
            'role' => $role,
            'status' => Membership::STATUS_ACTIVE,
            'invited_by_user_id' => $invitedBy,
            'joined_at' => now(),
        ]);
    }

    public function inviteToTenant(int $tenantId, string $role = Membership::ROLE_MEMBER, ?int $invitedBy = null): Membership
    {
        return $this->memberships()->create([
            'tenant_id' => $tenantId,
            'role' => $role,
            'status' => Membership::STATUS_PENDING,
            'invited_by_user_id' => $invitedBy,
        ]);
    }

    public function leaveTenant(int $tenantId): bool
    {
        $membership = $this->getMembershipFor($tenantId);

        if (!$membership) {
            return false;
        }

        // Owners cannot leave their tenant
        if ($membership->isOwner()) {
            return false;
        }

        return $membership->delete();
    }

    public function getAccessibleTenantsWithRole(): array
    {
        return $this->activeMemberships()
            ->with('tenant')
            ->get()
            ->map(function ($membership) {
                return [
                    'tenant' => $membership->tenant,
                    'role' => $membership->role,
                    'role_display' => $membership->getRoleDisplayName(),
                    'last_accessed' => $membership->last_accessed_at,
                    'joined_at' => $membership->joined_at,
                ];
            })
            ->toArray();
    }

}
