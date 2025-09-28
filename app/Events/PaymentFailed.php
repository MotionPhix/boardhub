<?php

namespace App\Events;

use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class PaymentFailed extends Event
{
    #[StateId(SubscriptionState::class)]
    public ?string $subscription_id = null;

    public function __construct(
        public array $payment_data,
    ) {
        // Extract subscription ID from payment data if available
        if (isset($payment_data['subscription_id'])) {
            $this->subscription_id = $payment_data['subscription_id'];
        } else {
            // Try to find by customer ID or other identifiers
            $this->subscription_id = $this->findSubscriptionId($payment_data);
        }
    }

    public function apply(SubscriptionState $state): void
    {
        $state->payment_status = 'failed';
        $state->failed_payment_attempts++;
        $state->last_failed_payment_at = now()->toISOString();

        // Update payment history
        $state->payment_history[] = [
            'event' => 'payment_failed',
            'timestamp' => now()->toISOString(),
            'payment_data' => $this->payment_data,
            'failure_reason' => $this->payment_data['failure_reason'] ?? 'Unknown',
        ];

        // If too many failed attempts, mark subscription as past_due
        if ($state->failed_payment_attempts >= 3) {
            $state->status = 'past_due';
        }

        // If more than 5 failed attempts, suspend the subscription
        if ($state->failed_payment_attempts >= 5) {
            $state->status = 'suspended';
            $state->suspended_at = now()->toISOString();
        }
    }

    public function handle(): void
    {
        // Find and update the tenant subscription
        $subscription = null;

        if ($this->subscription_id) {
            $subscription = TenantSubscription::where('paychangu_subscription_id', $this->subscription_id)->first();
        }

        // Try alternative lookups
        if (!$subscription && isset($this->payment_data['customer_id'])) {
            $subscription = TenantSubscription::where('paychangu_customer_id', $this->payment_data['customer_id'])->first();
        }

        if (!$subscription) {
            \Log::warning('No subscription found for failed payment', [
                'payment_data' => $this->payment_data,
                'subscription_id' => $this->subscription_id,
            ]);
            return;
        }

        // Update subscription status based on failure count
        $state = SubscriptionState::load($this->subscription_id);

        if ($state->failed_payment_attempts >= 5) {
            $subscription->update(['status' => 'suspended']);
        } elseif ($state->failed_payment_attempts >= 3) {
            $subscription->update(['status' => 'past_due']);
        }

        $subscription->update(['payment_status' => 'failed']);

        // Log activity
        activity()
            ->performedOn($subscription)
            ->withProperties([
                'payment_data' => $this->payment_data,
                'failure_reason' => $this->payment_data['failure_reason'] ?? 'Unknown',
                'failed_attempts' => $state->failed_payment_attempts,
            ])
            ->log('Subscription payment failed');

        // Fire tenant usage tracking
        if ($subscription->tenant) {
            \App\Events\TenantUsageTracked::fire(
                tenant_id: $subscription->tenant_id,
                action: 'subscription_payment_failed',
                metadata: [
                    'subscription_id' => $subscription->id,
                    'failure_reason' => $this->payment_data['failure_reason'] ?? 'Unknown',
                    'failed_attempts' => $state->failed_payment_attempts,
                    'payment_provider' => 'paychangu',
                ]
            );

            // Send notification based on severity
            $this->sendFailureNotification($subscription, $state);
        }
    }

    private function sendFailureNotification(TenantSubscription $subscription, SubscriptionState $state): void
    {
        $title = 'Payment Failed ❌';
        $priority = 'normal';

        if ($state->failed_payment_attempts >= 5) {
            $title = 'Subscription Suspended ⚠️';
            $message = 'Your subscription has been suspended due to multiple failed payments. Please update your payment method.';
            $priority = 'high';
        } elseif ($state->failed_payment_attempts >= 3) {
            $title = 'Payment Overdue ⏰';
            $message = 'Your subscription payment is overdue. Please update your payment method to avoid service interruption.';
            $priority = 'high';
        } else {
            $message = 'Your subscription payment failed. We\'ll try again in 24 hours.';
        }

        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $subscription->tenant_id,
            type: 'subscription_payment_failed',
            title: $title,
            message: $message,
            data: [
                'subscription_id' => $subscription->id,
                'failed_attempts' => $state->failed_payment_attempts,
                'failure_reason' => $this->payment_data['failure_reason'] ?? 'Unknown',
                'status' => $subscription->status,
            ],
            priority: $priority
        );
    }

    private function findSubscriptionId(array $payment_data): ?string
    {
        // Try to find subscription by various identifiers
        if (isset($payment_data['reference'])) {
            $subscription = TenantSubscription::where('reference', $payment_data['reference'])->first();
            if ($subscription) {
                return $subscription->paychangu_subscription_id;
            }
        }

        if (isset($payment_data['customer_id'])) {
            $subscription = TenantSubscription::where('paychangu_customer_id', $payment_data['customer_id'])->first();
            if ($subscription) {
                return $subscription->paychangu_subscription_id;
            }
        }

        return null;
    }
}