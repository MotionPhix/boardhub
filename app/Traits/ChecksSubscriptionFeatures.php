<?php

namespace App\Traits;

use App\Models\TenantSubscription;
use App\Services\SubscriptionService;

trait ChecksSubscriptionFeatures
{
    /**
     * Check if current tenant has access to a feature
     */
    protected function hasFeature(string $feature): bool
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return false;
        }

        return app(SubscriptionService::class)->hasFeature($subscription, $feature);
    }

    /**
     * Check if current tenant can use a feature based on limits
     */
    protected function canUseFeature(string $feature, ?string $limitType = null): bool
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return false;
        }

        return app(SubscriptionService::class)->canUseFeature($subscription, $feature, $limitType);
    }

    /**
     * Get feature usage for current tenant
     */
    protected function getFeatureUsage(string $feature): int
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return 0;
        }

        return app(SubscriptionService::class)->getFeatureUsage($subscription, $feature);
    }

    /**
     * Get feature limit for current tenant
     */
    protected function getFeatureLimit(string $feature): int
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return 0;
        }

        return app(SubscriptionService::class)->getFeatureLimit($subscription, $feature);
    }

    /**
     * Track feature usage for current tenant
     */
    protected function trackFeatureUsage(string $feature): void
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return;
        }

        app(SubscriptionService::class)->trackFeatureUsage($subscription, $feature);
    }

    /**
     * Get subscription status for current tenant
     */
    protected function getSubscriptionStatus(): array
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return [
                'status' => 'no_subscription',
                'is_active' => false,
                'needs_upgrade' => true,
            ];
        }

        return app(SubscriptionService::class)->getSubscriptionStatus($subscription);
    }

    /**
     * Check if subscription needs attention
     */
    protected function subscriptionNeedsAttention(): array
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return [[
                'type' => 'no_subscription',
                'severity' => 'high',
                'message' => 'No active subscription found',
                'action_required' => 'Subscribe to a plan',
            ]];
        }

        return app(SubscriptionService::class)->needsAttention($subscription);
    }

    /**
     * Get current subscription or throw error for API responses
     */
    protected function requireActiveSubscription(): TenantSubscription
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription) {
            if (request()->expectsJson()) {
                abort(403, 'No active subscription found');
            }

            redirect()->route('tenant.billing.plans', ['tenant' => auth()->user()->tenant->uuid])
                     ->with('error', 'Please subscribe to a plan to access this feature.')
                     ->send();
        }

        if (!$subscription->isActive() && !$subscription->isTrialActive()) {
            if (request()->expectsJson()) {
                abort(403, 'Subscription is not active');
            }

            redirect()->route('tenant.billing.plans', ['tenant' => auth()->user()->tenant->uuid])
                     ->with('error', 'Your subscription has expired. Please renew to continue.')
                     ->send();
        }

        return $subscription;
    }

    /**
     * Get current tenant subscription
     */
    private function getCurrentSubscription(): ?TenantSubscription
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id) {
            return null;
        }

        return $user->tenant->currentSubscription();
    }

    /**
     * Return standardized feature restriction response
     */
    protected function featureRestricted(string $feature, string $planRequired = 'Pro'): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $message = "The '{$feature}' feature requires a {$planRequired} plan or higher.";

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Feature Restricted',
                'message' => $message,
                'feature' => $feature,
                'plan_required' => $planRequired,
                'upgrade_url' => route('tenant.billing.plans', ['tenant' => auth()->user()->tenant->uuid]),
            ], 403);
        }

        return redirect()->back()
                        ->with('error', $message)
                        ->with('upgrade_required', true);
    }

    /**
     * Return standardized limit exceeded response
     */
    protected function limitExceeded(string $feature, int $limit, int $usage): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $message = "You've reached the {$feature} limit ({$usage}/{$limit}). Upgrade your plan to increase limits.";

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Limit Exceeded',
                'message' => $message,
                'feature' => $feature,
                'limit' => $limit,
                'usage' => $usage,
                'upgrade_url' => route('tenant.billing.plans', ['tenant' => auth()->user()->tenant->uuid]),
            ], 403);
        }

        return redirect()->back()
                        ->with('error', $message)
                        ->with('upgrade_required', true);
    }
}