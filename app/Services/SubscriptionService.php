<?php

namespace App\Services;

use App\Models\BillingPlan;
use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Check if subscription has access to a specific feature
     */
    public function hasFeature(TenantSubscription $subscription, string $feature): bool
    {
        if (!$subscription->isActive() && !$subscription->isTrialActive()) {
            return false;
        }

        $plan = $subscription->billingPlan;
        if (!$plan) {
            return false;
        }

        // Check if plan has this feature
        return $plan->hasFeature($feature);
    }

    /**
     * Check if subscription can use feature based on limits
     */
    public function canUseFeature(TenantSubscription $subscription, string $feature, ?string $limitType = null): bool
    {
        if (!$this->hasFeature($subscription, $feature)) {
            return false;
        }

        // If no limit specified, just check if feature exists
        if (!$limitType) {
            return true;
        }

        $limit = $this->getFeatureLimit($subscription, $feature);

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $usage = $this->getFeatureUsage($subscription, $feature);

        return $usage < $limit;
    }

    /**
     * Get feature limit for subscription
     */
    public function getFeatureLimit(TenantSubscription $subscription, string $feature): int
    {
        $plan = $subscription->billingPlan;
        if (!$plan) {
            return 0;
        }

        return $plan->getFeatureLimit($feature);
    }

    /**
     * Get current feature usage for subscription
     */
    public function getFeatureUsage(TenantSubscription $subscription, string $feature): int
    {
        $cacheKey = "subscription_usage_{$subscription->id}_{$feature}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($subscription, $feature) {
            $tenant = $subscription->tenant;

            switch ($feature) {
                case 'campaigns':
                case 'max_campaigns':
                    return $tenant->campaigns()->count();

                case 'billboards':
                case 'max_billboards':
                    return $tenant->billboards()->count();

                case 'team_members':
                case 'max_team_members':
                    return $tenant->members()->count();

                case 'bookings':
                case 'max_bookings':
                    return $tenant->bookings()->thisMonth()->count();

                case 'storage':
                case 'storage_gb':
                    return $this->calculateStorageUsage($tenant);

                case 'api_calls':
                case 'max_api_calls':
                    return $this->calculateApiUsage($tenant);

                case 'custom_domains':
                case 'max_custom_domains':
                    return $tenant->customDomains()->count();

                default:
                    return 0;
            }
        });
    }

    /**
     * Track feature usage
     */
    public function trackFeatureUsage(TenantSubscription $subscription, string $feature): void
    {
        // Clear cache to force refresh on next check
        $cacheKey = "subscription_usage_{$subscription->id}_{$feature}";
        Cache::forget($cacheKey);

        // Track usage in subscription state
        if ($subscription->paychangu_subscription_id) {
            $state = SubscriptionState::load($subscription->paychangu_subscription_id);

            // Update usage tracking in state
            $today = now()->format('Y-m-d');
            if (!isset($state->daily_usage)) {
                $state->daily_usage = [];
            }

            if (!isset($state->daily_usage[$today])) {
                $state->daily_usage[$today] = [];
            }

            if (!isset($state->daily_usage[$today][$feature])) {
                $state->daily_usage[$today][$feature] = 0;
            }

            $state->daily_usage[$today][$feature]++;
        }

        // Fire usage tracking event
        \App\Events\TenantUsageTracked::fire(
            tenant_id: $subscription->tenant_id,
            action: "feature_used_{$feature}",
            metadata: [
                'subscription_id' => $subscription->id,
                'feature' => $feature,
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * Get subscription status with detailed information
     */
    public function getSubscriptionStatus(TenantSubscription $subscription): array
    {
        $state = null;
        if ($subscription->paychangu_subscription_id) {
            $state = SubscriptionState::load($subscription->paychangu_subscription_id);
        }

        return [
            'status' => $subscription->status,
            'is_active' => $subscription->isActive(),
            'is_trial' => $subscription->status === 'trial',
            'trial_active' => $subscription->isTrialActive(),
            'is_expired' => $subscription->isExpired(),
            'days_until_expiration' => $subscription->daysUntilExpiration(),
            'payment_status' => $state?->getPaymentStatus() ?? 'unknown',
            'health_score' => $state?->getHealthScore() ?? 50,
            'next_billing_date' => $subscription->current_period_end,
            'plan' => [
                'name' => $subscription->billingPlan->name ?? 'unknown',
                'display_name' => $subscription->billingPlan->display_name ?? 'Unknown Plan',
                'price' => $subscription->amount,
                'currency' => $subscription->currency,
            ],
        ];
    }

    /**
     * Check if subscription needs attention (payment issues, expiring, etc.)
     */
    public function needsAttention(TenantSubscription $subscription): array
    {
        $issues = [];

        if (!$subscription->isActive() && !$subscription->isTrialActive()) {
            $issues[] = [
                'type' => 'inactive',
                'severity' => 'high',
                'message' => 'Subscription is not active',
                'action_required' => 'Reactivate subscription',
            ];
        }

        if ($subscription->isTrialActive() && $subscription->daysUntilExpiration() <= 3) {
            $issues[] = [
                'type' => 'trial_ending',
                'severity' => 'medium',
                'message' => "Trial ends in {$subscription->daysUntilExpiration()} days",
                'action_required' => 'Upgrade to a paid plan',
            ];
        }

        if ($subscription->status === 'past_due') {
            $issues[] = [
                'type' => 'payment_overdue',
                'severity' => 'high',
                'message' => 'Payment is overdue',
                'action_required' => 'Update payment method',
            ];
        }

        // Check feature limits
        $plan = $subscription->billingPlan;
        if ($plan) {
            foreach ($plan->features as $feature) {
                $limit = $this->getFeatureLimit($subscription, $feature);
                if ($limit > 0) {
                    $usage = $this->getFeatureUsage($subscription, $feature);
                    $percentage = ($usage / $limit) * 100;

                    if ($percentage >= 90) {
                        $issues[] = [
                            'type' => 'limit_approaching',
                            'severity' => 'medium',
                            'message' => "Approaching {$feature} limit ({$usage}/{$limit})",
                            'action_required' => 'Consider upgrading your plan',
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Get feature comparison between plans
     */
    public function getFeatureComparison(array $planIds): array
    {
        $plans = BillingPlan::whereIn('id', $planIds)
            ->with('planFeatures.feature')
            ->get();

        $comparison = [];
        $allFeatures = collect();

        // Collect all unique features
        foreach ($plans as $plan) {
            $allFeatures = $allFeatures->merge($plan->features);
        }
        $allFeatures = $allFeatures->unique();

        foreach ($allFeatures as $feature) {
            $comparison[$feature] = [];

            foreach ($plans as $plan) {
                $comparison[$feature][$plan->name] = [
                    'has_feature' => $plan->hasFeature($feature),
                    'limit' => $plan->getFeatureLimit($feature),
                ];
            }
        }

        return [
            'plans' => $plans->keyBy('name'),
            'features' => $comparison,
        ];
    }

    private function calculateStorageUsage($tenant): int
    {
        // Calculate storage usage in MB
        // This would typically involve checking file uploads, media library, etc.
        $totalSize = 0;

        // Check media library if using spatie/laravel-medialibrary
        if (method_exists($tenant, 'getMedia')) {
            $totalSize += $tenant->getMedia()->sum('size');
        }

        // Add other storage calculations as needed
        // Convert bytes to MB
        return round($totalSize / 1024 / 1024);
    }

    private function calculateApiUsage($tenant): int
    {
        // Calculate API usage for current month
        // This would typically check API logs or usage tracking table
        return DB::table('api_usage_logs')
            ->where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }
}