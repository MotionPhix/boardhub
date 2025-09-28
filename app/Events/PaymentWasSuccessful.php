<?php

namespace App\Events;

use App\Models\TenantSubscription;
use App\States\SubscriptionState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class PaymentWasSuccessful extends Event
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
        $state->payment_status = 'paid';
        $state->last_payment_at = now()->toISOString();
        $state->failed_payment_attempts = 0;

        // Update payment history
        $state->payment_history[] = [
            'event' => 'payment_successful',
            'timestamp' => now()->toISOString(),
            'payment_data' => $this->payment_data,
        ];

        // If this is a subscription renewal, update period
        if (isset($this->payment_data['type']) && $this->payment_data['type'] === 'subscription_renewal') {
            $state->status = 'active';

            // Extend subscription period
            if ($state->interval === 'monthly') {
                $state->current_period_end = now()->addMonth()->toISOString();
            } elseif ($state->interval === 'yearly') {
                $state->current_period_end = now()->addYear()->toISOString();
            }
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
            \Log::warning('No subscription found for successful payment', [
                'payment_data' => $this->payment_data,
                'subscription_id' => $this->subscription_id,
            ]);
            return;
        }

        // Update subscription status
        $subscription->update([
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        // Log activity
        activity()
            ->performedOn($subscription)
            ->withProperties([
                'payment_data' => $this->payment_data,
                'amount' => $this->payment_data['amount'] ?? null,
                'currency' => $this->payment_data['currency'] ?? null,
            ])
            ->log('Subscription payment successful');

        // Fire tenant usage tracking
        if ($subscription->tenant) {
            \App\Events\TenantUsageTracked::fire(
                tenant_id: $subscription->tenant_id,
                action: 'subscription_payment_successful',
                metadata: [
                    'subscription_id' => $subscription->id,
                    'payment_amount' => $this->payment_data['amount'] ?? null,
                    'payment_provider' => 'paychangu',
                ]
            );

            // Send notification
            \App\Events\RealTimeNotificationSent::fire(
                tenant_id: $subscription->tenant_id,
                type: 'subscription_payment_successful',
                title: 'Subscription Payment Successful! ✅',
                message: 'Your subscription has been renewed successfully',
                data: [
                    'subscription_id' => $subscription->id,
                    'amount' => $this->payment_data['amount'] ?? null,
                    'next_billing_date' => $subscription->current_period_end,
                ],
                priority: 'normal'
            );
        }
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