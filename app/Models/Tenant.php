<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'settings',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
        // Enhanced fields
        'logo_url',
        'primary_color',
        'secondary_color',
        'branding_settings',
        'business_type',
        'industry',
        'company_size',
        'contact_info',
        'subscription_tier',
        'feature_flags',
        'monthly_revenue',
        'user_limit',
        'billboard_limit',
        'usage_stats',
        'last_activity_at',
        'total_bookings',
        'total_revenue',
        'compliance_settings',
        'data_region',
        'requires_2fa',
        'onboarding_progress',
        'setup_completed',
        'activated_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        // Enhanced casts
        'branding_settings' => 'array',
        'contact_info' => 'array',
        'feature_flags' => 'array',
        'usage_stats' => 'array',
        'compliance_settings' => 'array',
        'onboarding_progress' => 'array',
        'monthly_revenue' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'setup_completed' => 'boolean',
        'requires_2fa' => 'boolean',
        'last_activity_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['role', 'status', 'permissions', 'joined_at', 'last_accessed_at'])
            ->wherePivot('status', Membership::STATUS_ACTIVE)
            ->withTimestamps();
    }

    public function owners(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('role', Membership::ROLE_OWNER);
    }

    public function admins(): BelongsToMany
    {
        return $this->activeMembers()->wherePivotIn('role', [Membership::ROLE_OWNER, Membership::ROLE_ADMIN]);
    }

    public function billboards(): HasMany
    {
        return $this->hasMany(Billboard::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain)->orWhere('subdomain', $domain);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (!$tenant->slug) {
                $tenant->slug = Str::slug($tenant->name);
            }

            // Initialize default settings
            $tenant->feature_flags = $tenant->feature_flags ?? self::getDefaultFeatureFlags();
            $tenant->onboarding_progress = $tenant->onboarding_progress ?? self::getDefaultOnboardingProgress();
            $tenant->branding_settings = $tenant->branding_settings ?? self::getDefaultBrandingSettings();
        });

        static::updating(function ($tenant) {
            // Track activity
            $tenant->last_activity_at = now();
        });
    }

    // Subscription tiers
    public const TIER_TRIAL = 'trial';
    public const TIER_STARTER = 'starter';
    public const TIER_PROFESSIONAL = 'professional';
    public const TIER_ENTERPRISE = 'enterprise';

    // Business types
    public const BUSINESS_AGENCY = 'advertising_agency';
    public const BUSINESS_BILLBOARD_OWNER = 'billboard_owner';
    public const BUSINESS_HYBRID = 'hybrid';

    /**
     * Get default feature flags for new tenants
     */
    public static function getDefaultFeatureFlags(): array
    {
        return [
            'advanced_analytics' => false,
            'api_access' => false,
            'white_labeling' => false,
            'custom_integrations' => false,
            'priority_support' => false,
            'mobile_app_access' => true,
            'real_time_notifications' => true,
            'basic_reporting' => true,
            'campaign_management' => true,
            'booking_automation' => false,
        ];
    }

    /**
     * Get default onboarding progress
     */
    public static function getDefaultOnboardingProgress(): array
    {
        return [
            'welcome_completed' => false,
            'profile_setup' => false,
            'branding_configured' => false,
            'first_billboard_added' => false,
            'first_client_added' => false,
            'payment_configured' => false,
            'team_invited' => false,
            'first_booking_created' => false,
        ];
    }

    /**
     * Get default branding settings
     */
    public static function getDefaultBrandingSettings(): array
    {
        return [
            'show_powered_by' => true,
            'custom_domain_enabled' => false,
            'custom_email_templates' => false,
            'custom_pdf_templates' => false,
            'dashboard_theme' => 'default',
        ];
    }

    /**
     * Check if tenant has specific feature enabled
     */
    public function hasFeature(string $feature): bool
    {
        return $this->feature_flags[$feature] ?? false;
    }

    /**
     * Enable a feature for tenant
     */
    public function enableFeature(string $feature): void
    {
        $flags = $this->feature_flags ?? [];
        $flags[$feature] = true;
        $this->update(['feature_flags' => $flags]);
    }

    /**
     * Get onboarding completion percentage
     */
    public function getOnboardingProgress(): int
    {
        $progress = $this->onboarding_progress ?? [];
        $total = count(self::getDefaultOnboardingProgress());
        $completed = count(array_filter($progress));

        return $total > 0 ? (int) (($completed / $total) * 100) : 0;
    }

    /**
     * Mark onboarding step as completed
     */
    public function completeOnboardingStep(string $step): void
    {
        $progress = $this->onboarding_progress ?? [];
        $progress[$step] = true;

        $this->update(['onboarding_progress' => $progress]);

        // Check if setup is completed
        if ($this->getOnboardingProgress() >= 80 && !$this->setup_completed) {
            $this->update([
                'setup_completed' => true,
                'activated_at' => now(),
            ]);
        }
    }

    /**
     * Get subscription limits based on tier
     */
    public function getSubscriptionLimits(): array
    {
        return match ($this->subscription_tier) {
            self::TIER_TRIAL => [
                'users' => 3,
                'billboards' => 10,
                'bookings_per_month' => 50,
                'storage_mb' => 100,
            ],
            self::TIER_STARTER => [
                'users' => 10,
                'billboards' => 100,
                'bookings_per_month' => 500,
                'storage_mb' => 1000,
            ],
            self::TIER_PROFESSIONAL => [
                'users' => 50,
                'billboards' => 500,
                'bookings_per_month' => 2000,
                'storage_mb' => 5000,
            ],
            self::TIER_ENTERPRISE => [
                'users' => -1, // unlimited
                'billboards' => -1,
                'bookings_per_month' => -1,
                'storage_mb' => 20000,
            ],
            default => [
                'users' => 3,
                'billboards' => 10,
                'bookings_per_month' => 50,
                'storage_mb' => 100,
            ],
        };
    }

    /**
     * Check if tenant is within limits
     */
    public function isWithinLimits(string $resource): bool
    {
        $limits = $this->getSubscriptionLimits();
        $limit = $limits[$resource] ?? 0;

        if ($limit === -1) {
            return true; // unlimited
        }

        $current = match ($resource) {
            'users' => $this->users()->count(),
            'billboards' => $this->billboards()->count(),
            'bookings_per_month' => $this->bookings()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            default => 0,
        };

        return $current < $limit;
    }

    /**
     * Update usage statistics
     */
    public function updateUsageStats(): void
    {
        $stats = [
            'users_count' => $this->users()->count(),
            'billboards_count' => $this->billboards()->count(),
            'active_bookings' => $this->bookings()->where('status', 'confirmed')->count(),
            'monthly_bookings' => $this->bookings()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'last_updated' => now()->toISOString(),
        ];

        $this->update(['usage_stats' => $stats]);
    }
}
