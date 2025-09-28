<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnforceSubscriptionLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:enforce-limits
                           {--dry-run : Show what would be done without making changes}
                           {--tenant= : Enforce limits for specific tenant only}
                           {--grace-period=24 : Grace period in hours for expired subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforce subscription limits and disable features for expired/over-limit tenants';

    public function __construct(
        private SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔒 Starting subscription limit enforcement...');

        $dryRun = $this->option('dry-run');
        $tenantFilter = $this->option('tenant');
        $gracePeriodHours = (int) $this->option('grace-period');

        if ($dryRun) {
            $this->warn('🚨 DRY RUN MODE - No changes will be made');
        }

        $stats = [
            'checked' => 0,
            'restricted' => 0,
            'unrestricted' => 0,
            'grace_period' => 0,
            'errors' => 0,
        ];

        // Get all tenants with subscriptions
        $subscriptions = $this->getSubscriptionsToEnforce($tenantFilter);

        $this->info("📊 Found {$subscriptions->count()} subscriptions to enforce");

        foreach ($subscriptions as $subscription) {
            $stats['checked']++;
            $this->enforceSubscriptionLimits($subscription, $dryRun, $gracePeriodHours, $stats);
        }

        $this->displaySummary($stats);

        return Command::SUCCESS;
    }

    private function getSubscriptionsToEnforce(?string $tenantFilter): \Illuminate\Database\Eloquent\Collection
    {
        $query = TenantSubscription::with(['tenant', 'billingPlan']);

        if ($tenantFilter) {
            $query->whereHas('tenant', function($q) use ($tenantFilter) {
                $q->where('uuid', $tenantFilter)->orWhere('slug', $tenantFilter);
            });
        }

        return $query->get();
    }

    private function enforceSubscriptionLimits(
        TenantSubscription $subscription,
        bool $dryRun,
        int $gracePeriodHours,
        array &$stats
    ): void {
        $tenant = $subscription->tenant;
        $tenantName = $tenant->name ?? 'Unknown';

        $this->line("🔎 Enforcing: {$tenantName} ({$subscription->status})");

        try {
            // Check if subscription is active or in grace period
            if ($this->shouldRestrictAccess($subscription, $gracePeriodHours)) {
                $this->restrictTenantAccess($tenant, $subscription, $dryRun, $stats);
            } else {
                $this->ensureTenantAccess($tenant, $subscription, $dryRun, $stats);
            }

            // Enforce feature limits regardless of subscription status
            $this->enforceFeatureLimits($tenant, $subscription, $dryRun);

        } catch (\Exception $e) {
            $stats['errors']++;
            $this->error("❌ Error enforcing limits for {$tenantName}: {$e->getMessage()}");
        }
    }

    private function shouldRestrictAccess(TenantSubscription $subscription, int $gracePeriodHours): bool
    {
        // Check if subscription is expired
        if (!$subscription->isExpired()) {
            return false;
        }

        // Check grace period for recently expired subscriptions
        $expirationTime = $subscription->current_period_end ?? $subscription->trial_ends_at;
        if ($expirationTime) {
            $gracePeriodEnd = \Carbon\Carbon::parse($expirationTime)->addHours($gracePeriodHours);
            if (now()->lt($gracePeriodEnd)) {
                return false; // Still in grace period
            }
        }

        // Restrict access for suspended or cancelled subscriptions
        return in_array($subscription->status, ['expired', 'suspended', 'cancelled']);
    }

    private function restrictTenantAccess(
        \App\Models\Tenant $tenant,
        TenantSubscription $subscription,
        bool $dryRun,
        array &$stats
    ): void {
        if ($tenant->is_active) {
            $this->warn("🔒 RESTRICTING: {$tenant->name} - subscription expired");

            if (!$dryRun) {
                // Disable tenant access
                $tenant->update([
                    'is_active' => false,
                    'feature_flags' => $this->getRestrictedFeatureFlags(),
                ]);

                // Log the restriction
                activity()
                    ->performedOn($tenant)
                    ->withProperties([
                        'subscription_id' => $subscription->id,
                        'reason' => 'subscription_expired',
                        'previous_status' => $subscription->status,
                    ])
                    ->log('Tenant access restricted due to expired subscription');

                // Send notification
                $this->sendAccessRestrictedNotification($tenant, $subscription);
            }

            $stats['restricted']++;
        }
    }

    private function ensureTenantAccess(
        \App\Models\Tenant $tenant,
        TenantSubscription $subscription,
        bool $dryRun,
        array &$stats
    ): void {
        if (!$tenant->is_active && ($subscription->isActive() || $subscription->isTrialActive())) {
            $this->info("🔓 RESTORING: {$tenant->name} - subscription is active");

            if (!$dryRun) {
                // Restore tenant access
                $plan = $subscription->billingPlan;
                $featureFlags = $plan ? $this->getPlanFeatureFlags($plan) : \App\Models\Tenant::getDefaultFeatureFlags();

                $tenant->update([
                    'is_active' => true,
                    'feature_flags' => $featureFlags,
                ]);

                // Log the restoration
                activity()
                    ->performedOn($tenant)
                    ->withProperties([
                        'subscription_id' => $subscription->id,
                        'reason' => 'subscription_active',
                        'current_status' => $subscription->status,
                    ])
                    ->log('Tenant access restored due to active subscription');

                // Send notification
                $this->sendAccessRestoredNotification($tenant, $subscription);
            }

            $stats['unrestricted']++;
        }
    }

    private function enforceFeatureLimits(
        \App\Models\Tenant $tenant,
        TenantSubscription $subscription,
        bool $dryRun
    ): void {
        $plan = $subscription->billingPlan;
        if (!$plan) {
            return;
        }

        // Check each feature limit
        foreach ($plan->features as $feature) {
            $limit = $this->subscriptionService->getFeatureLimit($subscription, $feature);
            if ($limit <= 0) continue; // Skip unlimited features

            $usage = $this->subscriptionService->getFeatureUsage($subscription, $feature);

            if ($usage > $limit) {
                $this->warn("📊 OVER LIMIT: {$tenant->name} - {$feature}: {$usage}/{$limit}");

                if (!$dryRun) {
                    $this->handleOverLimitFeature($tenant, $feature, $usage, $limit);
                }
            }
        }
    }

    private function handleOverLimitFeature(
        \App\Models\Tenant $tenant,
        string $feature,
        int $usage,
        int $limit
    ): void {
        // Handle specific over-limit scenarios
        switch ($feature) {
            case 'max_campaigns':
                $this->disableOldestCampaigns($tenant, $usage - $limit);
                break;

            case 'max_team_members':
                $this->suspendLatestTeamMembers($tenant, $usage - $limit);
                break;

            case 'storage_gb':
                $this->notifyStorageOverage($tenant, $usage, $limit);
                break;

            case 'max_api_calls':
                $this->throttleApiAccess($tenant);
                break;

            default:
                $this->notifyFeatureOverage($tenant, $feature, $usage, $limit);
                break;
        }
    }

    private function getRestrictedFeatureFlags(): array
    {
        return [
            'advanced_analytics' => false,
            'api_access' => false,
            'white_labeling' => false,
            'custom_integrations' => false,
            'priority_support' => false,
            'mobile_app_access' => false,
            'real_time_notifications' => false,
            'basic_reporting' => false,
            'campaign_management' => false,
            'booking_automation' => false,
        ];
    }

    private function getPlanFeatureFlags(\App\Models\BillingPlan $plan): array
    {
        // Map plan features to feature flags
        $defaultFlags = \App\Models\Tenant::getDefaultFeatureFlags();

        foreach ($plan->features as $feature) {
            switch ($feature) {
                case 'advanced_analytics':
                    $defaultFlags['advanced_analytics'] = true;
                    break;
                case 'api_access':
                    $defaultFlags['api_access'] = true;
                    break;
                case 'white_labeling':
                    $defaultFlags['white_labeling'] = true;
                    break;
                case 'priority_support':
                    $defaultFlags['priority_support'] = true;
                    break;
                // Add more feature mappings as needed
            }
        }

        return $defaultFlags;
    }

    private function disableOldestCampaigns(\App\Models\Tenant $tenant, int $excess): void
    {
        // Disable oldest campaigns that exceed the limit
        $campaigns = $tenant->campaigns()->oldest()->limit($excess)->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'suspended']);
        }

        $this->info("🔻 Suspended {$excess} oldest campaigns for {$tenant->name}");
    }

    private function suspendLatestTeamMembers(\App\Models\Tenant $tenant, int $excess): void
    {
        // Suspend latest team members that exceed the limit
        $members = $tenant->memberships()
                          ->where('status', 'active')
                          ->where('role', '!=', 'owner')
                          ->latest()
                          ->limit($excess)
                          ->get();

        foreach ($members as $membership) {
            $membership->update(['status' => 'suspended']);
        }

        $this->info("👥 Suspended {$excess} team members for {$tenant->name}");
    }

    private function notifyStorageOverage(\App\Models\Tenant $tenant, int $usage, int $limit): void
    {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $tenant->id,
            type: 'storage_overage',
            title: 'Storage Limit Exceeded 💾',
            message: "You're using {$usage}GB of {$limit}GB storage. Please upgrade or delete files.",
            data: [
                'usage_gb' => $usage,
                'limit_gb' => $limit,
                'overage_gb' => $usage - $limit,
            ],
            priority: 'high'
        );
    }

    private function throttleApiAccess(\App\Models\Tenant $tenant): void
    {
        // Implementation would depend on your API throttling system
        $this->info("🚦 API access throttled for {$tenant->name}");
    }

    private function notifyFeatureOverage(\App\Models\Tenant $tenant, string $feature, int $usage, int $limit): void
    {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $tenant->id,
            type: 'feature_overage',
            title: 'Feature Limit Exceeded 📊',
            message: "You've exceeded the {$feature} limit ({$usage}/{$limit}). Please upgrade your plan.",
            data: [
                'feature' => $feature,
                'usage' => $usage,
                'limit' => $limit,
            ],
            priority: 'medium'
        );
    }

    private function sendAccessRestrictedNotification(\App\Models\Tenant $tenant, TenantSubscription $subscription): void
    {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $tenant->id,
            type: 'access_restricted',
            title: 'Account Access Restricted 🚫',
            message: 'Your account has been restricted due to subscription expiration. Please renew to restore access.',
            data: [
                'subscription_id' => $subscription->id,
                'restriction_reason' => 'subscription_expired',
            ],
            priority: 'high'
        );
    }

    private function sendAccessRestoredNotification(\App\Models\Tenant $tenant, TenantSubscription $subscription): void
    {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $tenant->id,
            type: 'access_restored',
            title: 'Account Access Restored ✅',
            message: 'Your account access has been restored. Welcome back!',
            data: [
                'subscription_id' => $subscription->id,
                'restoration_reason' => 'subscription_active',
            ],
            priority: 'normal'
        );
    }

    private function displaySummary(array $stats): void
    {
        $this->info('');
        $this->info('🔒 ENFORCEMENT SUMMARY');
        $this->info('======================');
        $this->info("✅ Subscriptions checked: {$stats['checked']}");
        $this->info("🔒 Access restricted: {$stats['restricted']}");
        $this->info("🔓 Access restored: {$stats['unrestricted']}");
        $this->info("⏰ In grace period: {$stats['grace_period']}");
        $this->info("❌ Errors: {$stats['errors']}");

        if ($stats['restricted'] > 0) {
            $this->warn('⚠️  Some tenants have been restricted!');
        }

        if ($stats['errors'] > 0) {
            $this->error('💥 Some errors occurred during enforcement!');
        }

        if ($stats['restricted'] === 0 && $stats['errors'] === 0) {
            $this->info('🎉 All subscriptions are properly enforced!');
        }
    }
}