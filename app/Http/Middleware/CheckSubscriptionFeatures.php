<?php

namespace App\Http\Middleware;

use App\Models\TenantSubscription;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckSubscriptionFeatures
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature, ?string $limit = null): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return $this->unauthorized('No active tenant found');
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            return $this->unauthorized('Invalid tenant');
        }

        // Get current subscription
        $subscription = $tenant->currentSubscription();

        if (!$subscription) {
            return $this->unauthorized('No active subscription found');
        }

        // Check if subscription allows this feature
        if (!$this->subscriptionService->hasFeature($subscription, $feature)) {
            return $this->featureNotAllowed($feature, $subscription);
        }

        // Check feature limits if specified
        if ($limit && !$this->subscriptionService->canUseFeature($subscription, $feature, $limit)) {
            return $this->featureLimitExceeded($feature, $limit, $subscription);
        }

        // Track feature usage
        $this->subscriptionService->trackFeatureUsage($subscription, $feature);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message,
            ], 401);
        }

        return redirect('/login')->with('error', $message);
    }

    private function featureNotAllowed(string $feature, TenantSubscription $subscription): Response
    {
        $planName = $subscription->billingPlan->display_name ?? 'current plan';
        $message = "The '{$feature}' feature is not available on your {$planName}. Please upgrade to access this feature.";

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Feature Not Available',
                'message' => $message,
                'feature' => $feature,
                'current_plan' => $subscription->billingPlan->name ?? 'unknown',
                'upgrade_required' => true,
                'upgrade_url' => route('tenant.billing.plans', ['tenant' => $subscription->tenant->uuid]),
            ], 403);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->with('upgrade_required', true)
            ->with('upgrade_url', route('tenant.billing.plans', ['tenant' => $subscription->tenant->uuid]));
    }

    private function featureLimitExceeded(string $feature, string $limit, TenantSubscription $subscription): Response
    {
        $planName = $subscription->billingPlan->display_name ?? 'current plan';
        $limitValue = $this->subscriptionService->getFeatureLimit($subscription, $feature);
        $usage = $this->subscriptionService->getFeatureUsage($subscription, $feature);

        $message = "You've reached the {$feature} limit for your {$planName} ({$usage}/{$limitValue}). Please upgrade to increase your limits.";

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Feature Limit Exceeded',
                'message' => $message,
                'feature' => $feature,
                'limit' => $limitValue,
                'usage' => $usage,
                'current_plan' => $subscription->billingPlan->name ?? 'unknown',
                'upgrade_required' => true,
                'upgrade_url' => route('tenant.billing.plans', ['tenant' => $subscription->tenant->uuid]),
            ], 403);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->with('upgrade_required', true)
            ->with('upgrade_url', route('tenant.billing.plans', ['tenant' => $subscription->tenant->uuid]));
    }
}