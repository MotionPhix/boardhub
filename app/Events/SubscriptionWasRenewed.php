<?php

namespace App\Events;

use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class SubscriptionWasRenewed extends Event
{
    #[StateId(SubscriptionState::class)]
    public string $subscription_id;

    public function __construct(
        public array $subscription_data,
    ) {
        $this->subscription_id = $subscription_data['subscription_id'] ?? $subscription_data['id'];
    }

    public function apply(SubscriptionState $state): void
    {
        $state->status = 'active';
        $state->payment_status = 'paid';
        $state->failed_payment_attempts = 0;
        $state->last_payment_at = now()->toISOString();

        // Update billing period
        $previousPeriodEnd = $state->current_period_end ? \Carbon\Carbon::parse($state->current_period_end) : now();
        $state->current_period_start = $previousPeriodEnd->toISOString();

        if ($state->interval === 'monthly') {
            $state->current_period_end = $previousPeriodEnd->addMonth()->toISOString();
        } elseif ($state->interval === 'yearly') {
            $state->current_period_end = $previousPeriodEnd->addYear()->toISOString();
        }

        // Clear any trial or cancellation data
        $state->trial_ends_at = null;
        $state->cancelled_at = null;
        $state->ends_at = null;

        // Update subscription history
        $state->subscription_history[] = [
            'event' => 'subscription_renewed',
            'timestamp' => now()->toISOString(),
            'new_period_start' => $state->current_period_start,
            'new_period_end' => $state->current_period_end,
            'amount' => $this->subscription_data['amount'] ?? null,
        ];

        // Track renewal metrics
        $state->renewal_count++;
        $state->total_revenue += $this->subscription_data['amount'] ?? 0;
    }

    public function handle(): void
    {
        // Find and update the tenant subscription
        $subscription = TenantSubscription::where('paychangu_subscription_id', $this->subscription_id)->first();

        if (!$subscription) {
            \Log::warning('No subscription found for renewal', [
                'subscription_data' => $this->subscription_data,
                'subscription_id' => $this->subscription_id,
            ]);
            return;
        }

        // Update subscription with new period
        $state = SubscriptionState::load($this->subscription_id);

        $subscription->update([
            'status' => 'active',
            'payment_status' => 'paid',
            'current_period_start' => $state->current_period_start,
            'current_period_end' => $state->current_period_end,
            'cancelled_at' => null,
            'expires_at' => null,
        ]);

        // Log activity
        activity()
            ->performedOn($subscription)
            ->withProperties([
                'subscription_data' => $this->subscription_data,
                'amount' => $this->subscription_data['amount'] ?? null,
                'new_period_end' => $state->current_period_end,
                'renewal_count' => $state->renewal_count,
            ])
            ->log('Subscription renewed successfully');

        // Fire tenant usage tracking
        if ($subscription->tenant) {
            \App\Events\TenantUsageTracked::fire(
                tenant_id: $subscription->tenant_id,
                action: 'subscription_renewed',
                metadata: [
                    'subscription_id' => $subscription->id,
                    'amount' => $this->subscription_data['amount'] ?? null,
                    'renewal_count' => $state->renewal_count,
                    'new_period_end' => $state->current_period_end,
                    'payment_provider' => 'paychangu',
                ]
            );

            // Send renewal notification
            \App\Events\RealTimeNotificationSent::fire(
                tenant_id: $subscription->tenant_id,
                type: 'subscription_renewed',
                title: 'Subscription Renewed! 🔄',
                message: $this->getRenewalMessage($subscription, $state),
                data: [
                    'subscription_id' => $subscription->id,
                    'amount' => $this->subscription_data['amount'] ?? null,
                    'next_billing_date' => $state->current_period_end,
                    'plan_name' => $subscription->billingPlan->display_name ?? 'Unknown Plan',
                    'renewal_count' => $state->renewal_count,
                ],
                priority: 'normal'
            );

            // Fire subscription upgrade event if this was a plan change
            $this->checkForPlanUpgrade($subscription);

            // Update tenant revenue statistics
            $this->updateTenantRevenue($subscription, $this->subscription_data['amount'] ?? 0);
        }
    }

    private function getRenewalMessage(TenantSubscription $subscription, SubscriptionState $state): string
    {
        $nextBilling = \Carbon\Carbon::parse($state->current_period_end)->format('M j, Y');
        $planName = $subscription->billingPlan->display_name ?? 'your plan';

        return "Your {$planName} subscription has been renewed. Next billing date: {$nextBilling}";
    }

    private function checkForPlanUpgrade(TenantSubscription $subscription): void
    {
        // Check if the renewed subscription has a different plan than before
        if (isset($this->subscription_data['plan_id'])) {
            $newPlanId = $this->subscription_data['plan_id'];
            $currentPlan = $subscription->billingPlan;

            if ($currentPlan && $currentPlan->name !== $newPlanId) {
                $newPlan = \App\Models\BillingPlan::where('name', $newPlanId)->first();

                if ($newPlan) {
                    // Update subscription plan
                    $subscription->update(['billing_plan_id' => $newPlan->id]);

                    // Fire upgrade event
                    \App\Events\TenantSubscriptionUpgraded::fire(
                        tenant_id: $subscription->tenant_id,
                        subscription_id: $subscription->id,
                        old_plan_id: $currentPlan->id,
                        new_plan_id: $newPlan->id,
                        upgraded_via: 'renewal'
                    );
                }
            }
        }
    }

    private function updateTenantRevenue(TenantSubscription $subscription, float $amount): void
    {
        if ($amount <= 0) return;

        $tenant = $subscription->tenant;

        // Update total revenue
        $tenant->increment('total_revenue', $amount);

        // Update monthly revenue
        $monthlyRevenue = $tenant->subscriptions()
            ->where('status', 'active')
            ->sum('amount');

        $tenant->update(['monthly_revenue' => $monthlyRevenue]);

        // Fire revenue tracking event
        \App\Events\TenantUsageTracked::fire(
            tenant_id: $tenant->id,
            action: 'subscription_revenue_generated',
            metadata: [
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'revenue_type' => 'subscription_renewal',
                'payment_provider' => 'paychangu',
            ]
        );
    }
}