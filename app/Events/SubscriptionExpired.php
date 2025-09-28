<?php

namespace App\Events;

use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class SubscriptionExpired extends Event
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
        $state->status = 'expired';
        $state->expired_at = now()->toISOString();
        $state->payment_status = 'unpaid';

        // Update subscription history
        $state->subscription_history[] = [
            'event' => 'subscription_expired',
            'timestamp' => now()->toISOString(),
            'expiration_reason' => $this->subscription_data['expiration_reason'] ?? 'billing_cycle_ended',
            'failed_attempts' => $state->failed_payment_attempts,
        ];

        // Track metrics
        $totalDays = $state->current_period_start && $state->current_period_end
            ? \Carbon\Carbon::parse($state->current_period_start)->diffInDays($state->current_period_end)
            : 0;

        $state->lifetime_value = $state->total_revenue;
        $state->subscription_duration_days = $totalDays;
    }

    public function handle(): void
    {
        // Find and update the tenant subscription
        $subscription = TenantSubscription::where('paychangu_subscription_id', $this->subscription_id)->first();

        if (!$subscription) {
            \Log::warning('No subscription found for expiration', [
                'subscription_data' => $this->subscription_data,
                'subscription_id' => $this->subscription_id,
            ]);
            return;
        }

        // Update subscription status
        $subscription->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);

        // Log activity
        activity()
            ->performedOn($subscription)
            ->withProperties([
                'subscription_data' => $this->subscription_data,
                'expiration_reason' => $this->subscription_data['expiration_reason'] ?? 'billing_cycle_ended',
                'was_cancelled' => $subscription->cancelled_at !== null,
            ])
            ->log('Subscription expired');

        // Fire tenant usage tracking
        if ($subscription->tenant) {
            $state = SubscriptionState::load($this->subscription_id);

            \App\Events\TenantUsageTracked::fire(
                tenant_id: $subscription->tenant_id,
                action: 'subscription_expired',
                metadata: [
                    'subscription_id' => $subscription->id,
                    'expiration_reason' => $this->subscription_data['expiration_reason'] ?? 'billing_cycle_ended',
                    'lifetime_value' => $state->lifetime_value,
                    'subscription_duration_days' => $state->subscription_duration_days,
                    'was_cancelled' => $subscription->cancelled_at !== null,
                ]
            );

            // Send expiration notification
            \App\Events\RealTimeNotificationSent::fire(
                tenant_id: $subscription->tenant_id,
                type: 'subscription_expired',
                title: 'Subscription Expired ⏰',
                message: $this->getExpirationMessage($subscription),
                data: [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->billingPlan->display_name ?? 'Unknown Plan',
                    'expired_at' => now()->toISOString(),
                ],
                priority: 'high'
            );

            // Handle automatic downgrade to trial
            $this->handleExpirationDowngrade($subscription);

            // Send reactivation reminder (delayed notification)
            $this->scheduleReactivationReminder($subscription);
        }
    }

    private function getExpirationMessage(TenantSubscription $subscription): string
    {
        $planName = $subscription->billingPlan->display_name ?? 'your plan';

        if ($subscription->cancelled_at) {
            return "Your {$planName} subscription has ended as scheduled. Thanks for being a customer!";
        } else {
            return "Your {$planName} subscription has expired. Reactivate now to restore full access to your features.";
        }
    }

    private function handleExpirationDowngrade(TenantSubscription $subscription): void
    {
        // Check if we should downgrade to trial or free plan
        $trialPlan = \App\Models\BillingPlan::where('name', 'trial')->first();

        if (!$trialPlan) {
            \Log::warning('No trial plan found for downgrade', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
            ]);
            return;
        }

        // Create new trial subscription
        $newSubscription = TenantSubscription::create([
            'tenant_id' => $subscription->tenant_id,
            'billing_plan_id' => $trialPlan->id,
            'status' => 'trial',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($trialPlan->trial_days ?? 14),
            'trial_ends_at' => now()->addDays($trialPlan->trial_days ?? 14),
        ]);

        // Log the downgrade
        activity()
            ->performedOn($newSubscription)
            ->withProperties([
                'previous_subscription_id' => $subscription->id,
                'downgrade_reason' => 'subscription_expired',
                'trial_duration_days' => $trialPlan->trial_days ?? 14,
            ])
            ->log('Automatically downgraded to trial after expiration');

        // Fire downgrade event
        \App\Events\TenantSubscriptionUpgraded::fire(
            tenant_id: $subscription->tenant_id,
            subscription_id: $newSubscription->id,
            old_plan_id: $subscription->billing_plan_id,
            new_plan_id: $trialPlan->id,
            upgraded_via: 'expiration_downgrade'
        );

        // Notify about downgrade
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $subscription->tenant_id,
            type: 'subscription_downgraded',
            title: 'Account Moved to Trial 📉',
            message: "Your account has been moved to our trial plan. You have {$trialPlan->trial_days} days to upgrade and restore full features.",
            data: [
                'new_subscription_id' => $newSubscription->id,
                'trial_ends_at' => $newSubscription->trial_ends_at,
                'trial_duration_days' => $trialPlan->trial_days ?? 14,
            ],
            priority: 'high'
        );
    }

    private function scheduleReactivationReminder(TenantSubscription $subscription): void
    {
        // Schedule a reminder notification for 7 days after expiration
        // This would typically be done with a queued job or scheduled command

        $reminderData = [
            'tenant_id' => $subscription->tenant_id,
            'expired_subscription_id' => $subscription->id,
            'plan_name' => $subscription->billingPlan->display_name ?? 'Premium Plan',
            'expired_at' => now()->toISOString(),
        ];

        // Log that we should send a reminder
        \Log::info('Scheduled reactivation reminder', $reminderData);

        // In a real application, you would dispatch a delayed job here:
        // \App\Jobs\SendReactivationReminder::dispatch($reminderData)->delay(now()->addDays(7));
    }
}