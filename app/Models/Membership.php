<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
        'status',
        'permissions',
        'invited_by_user_id',
        'invitation_token',
        'invited_at',
        'joined_at',
        'last_accessed_at',
        'access_restrictions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'access_restrictions' => 'array',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    // Relationship constants
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUSPENDED = 'suspended';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    public function canManage(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_values(array_filter($permissions, fn($p) => $p !== $permission));
        $this->update(['permissions' => $permissions]);
    }

    public function updateLastAccess(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'joined_at' => $this->joined_at ?? now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    // Static methods for role hierarchy
    public static function getRoleHierarchy(): array
    {
        return [
            self::ROLE_OWNER => 5,
            self::ROLE_ADMIN => 4,
            self::ROLE_MANAGER => 3,
            self::ROLE_MEMBER => 2,
            self::ROLE_VIEWER => 1,
        ];
    }

    public function getRoleLevel(): int
    {
        return self::getRoleHierarchy()[$this->role] ?? 0;
    }

    public function canManageRole(string $targetRole): bool
    {
        $hierarchy = self::getRoleHierarchy();
        $myLevel = $hierarchy[$this->role] ?? 0;
        $targetLevel = $hierarchy[$targetRole] ?? 0;

        return $myLevel > $targetLevel;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            // Generate invitation token if status is pending
            if ($membership->status === self::STATUS_PENDING && !$membership->invitation_token) {
                $membership->invitation_token = Str::random(64);
            }

            // Set invited_at timestamp
            if ($membership->status === self::STATUS_PENDING && !$membership->invited_at) {
                $membership->invited_at = now();
            }
        });

        static::updating(function ($membership) {
            // Set joined_at when status changes to active
            if ($membership->isDirty('status') && $membership->status === self::STATUS_ACTIVE && !$membership->joined_at) {
                $membership->joined_at = now();
            }
        });
    }

    /**
     * Generate display name for role
     */
    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            self::ROLE_OWNER => 'Owner',
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_MEMBER => 'Member',
            self::ROLE_VIEWER => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get available roles for assignment based on current user's role
     */
    public static function getAvailableRoles(string $currentUserRole): array
    {
        $hierarchy = self::getRoleHierarchy();
        $currentLevel = $hierarchy[$currentUserRole] ?? 0;

        return array_keys(array_filter($hierarchy, fn($level) => $level < $currentLevel));
    }
}