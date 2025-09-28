<?php

namespace App\Events;

use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class SubscriptionWasCancelled extends Event
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
        $state->status = 'cancelled';
        $state->cancelled_at = now()->toISOString();
        $state->cancellation_reason = $this->subscription_data['cancellation_reason'] ?? 'user_requested';

        // Keep subscription active until current period ends
        if ($state->current_period_end && now()->lt($state->current_period_end)) {
            $state->ends_at = $state->current_period_end;
        } else {
            $state->ends_at = now()->toISOString();
        }

        // Update subscription history
        $state->subscription_history[] = [
            'event' => 'subscription_cancelled',
            'timestamp' => now()->toISOString(),
            'reason' => $state->cancellation_reason,
            'ends_at' => $state->ends_at,
        ];
    }

    public function handle(): void
    {
        // Find and update the tenant subscription
        $subscription = TenantSubscription::where('paychangu_subscription_id', $this->subscription_id)->first();

        if (!$subscription) {
            \Log::warning('No subscription found for cancellation', [
                'subscription_data' => $this->subscription_data,
                'subscription_id' => $this->subscription_id,
            ]);
            return;
        }

        // Update subscription status
        $state = SubscriptionState::load($this->subscription_id);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'expires_at' => $state->ends_at ? \Carbon\Carbon::parse($state->ends_at) : now(),
        ]);

        // Log activity
        activity()
            ->performedOn($subscription)
            ->withProperties([
                'subscription_data' => $this->subscription_data,
                'cancellation_reason' => $state->cancellation_reason,
                'ends_at' => $state->ends_at,
            ])
            ->log('Subscription cancelled');

        // Fire tenant usage tracking
        if ($subscription->tenant) {
            \App\Events\TenantUsageTracked::fire(
                tenant_id: $subscription->tenant_id,
                action: 'subscription_cancelled',
                metadata: [
                    'subscription_id' => $subscription->id,
                    'cancellation_reason' => $state->cancellation_reason,
                    'ends_at' => $state->ends_at,
                    'was_active' => $subscription->isActive(),
                ]
            );

            // Send cancellation notification
            \App\Events\RealTimeNotificationSent::fire(
                tenant_id: $subscription->tenant_id,
                type: 'subscription_cancelled',
                title: 'Subscription Cancelled 📋',
                message: $this->getCancellationMessage($subscription, $state),
                data: [
                    'subscription_id' => $subscription->id,
                    'cancellation_reason' => $state->cancellation_reason,
                    'ends_at' => $state->ends_at,
                    'plan_name' => $subscription->billingPlan->display_name ?? 'Unknown Plan',
                ],
                priority: 'normal'
            );

            // If subscription ends immediately, fire downgrade event
            if ($state->ends_at && now()->gte($state->ends_at)) {
                $this->handleImmediateDowngrade($subscription);
            }
        }
    }

    private function getCancellationMessage(TenantSubscription $subscription, SubscriptionState $state): string
    {
        if ($state->ends_at && now()->lt($state->ends_at)) {
            $endsAt = \Carbon\Carbon::parse($state->ends_at)->format('M j, Y');
            return "Your subscription has been cancelled and will end on {$endsAt}. You'll continue to have access until then.";
        } else {
            return "Your subscription has been cancelled and will end immediately.";
        }
    }

    private function handleImmediateDowngrade(TenantSubscription $subscription): void
    {
        // Downgrade to trial plan or free tier
        $trialPlan = \App\Models\BillingPlan::where('name', 'trial')->first();

        if ($trialPlan) {
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
                    'downgrade_reason' => 'subscription_cancelled',
                ])
                ->log('Automatically downgraded to trial after cancellation');

            // Notify about downgrade
            \App\Events\RealTimeNotificationSent::fire(
                tenant_id: $subscription->tenant_id,
                type: 'subscription_downgraded',
                title: 'Account Downgraded to Trial 📉',
                message: 'Your account has been moved to our trial plan. Upgrade anytime to restore full features.',
                data: [
                    'new_subscription_id' => $newSubscription->id,
                    'trial_ends_at' => $newSubscription->trial_ends_at,
                ],
                priority: 'normal'
            );
        }
    }
}