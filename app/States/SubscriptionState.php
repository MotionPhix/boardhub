<?php

namespace App\States;

use Thunk\Verbs\State;

class SubscriptionState extends State
{
    // Basic subscription info
    public string $status = 'trial'; // trial, active, cancelled, expired, suspended, past_due
    public string $payment_status = 'pending'; // pending, paid, failed, unpaid
    public string $interval = 'monthly'; // monthly, yearly
    public float $amount = 0.0;
    public string $currency = 'USD';

    // PayChangu integration
    public ?string $paychangu_subscription_id = null;
    public ?string $paychangu_customer_id = null;

    // Billing periods
    public ?string $current_period_start = null;
    public ?string $current_period_end = null;
    public ?string $trial_ends_at = null;

    // Cancellation and expiration
    public ?string $cancelled_at = null;
    public ?string $expires_at = null;
    public ?string $expired_at = null;
    public ?string $suspended_at = null;
    public ?string $ends_at = null;
    public ?string $cancellation_reason = null;

    // Payment tracking
    public int $failed_payment_attempts = 0;
    public ?string $last_payment_at = null;
    public ?string $last_failed_payment_at = null;

    // Metrics and analytics
    public int $renewal_count = 0;
    public float $total_revenue = 0.0;
    public float $lifetime_value = 0.0;
    public int $subscription_duration_days = 0;

    // History tracking
    public array $payment_history = [];
    public array $subscription_history = [];

    // Helper methods for state checking
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->current_period_end &&
               now()->lt($this->current_period_end);
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               now()->lt($this->trial_ends_at);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->current_period_end && now()->gt($this->current_period_end)) ||
               ($this->trial_ends_at && now()->gt($this->trial_ends_at));
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due' || $this->failed_payment_attempts >= 3;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || $this->suspended_at !== null;
    }

    public function daysUntilExpiration(): int
    {
        $expirationDate = null;

        if ($this->status === 'trial' && $this->trial_ends_at) {
            $expirationDate = $this->trial_ends_at;
        } elseif ($this->current_period_end) {
            $expirationDate = $this->current_period_end;
        }

        if (!$expirationDate) {
            return 0;
        }

        return max(0, now()->diffInDays($expirationDate, false));
    }

    public function getPaymentStatus(): string
    {
        if ($this->isSuspended()) {
            return 'suspended';
        }

        if ($this->isPastDue()) {
            return 'past_due';
        }

        if ($this->failed_payment_attempts > 0) {
            return 'payment_issues';
        }

        return $this->payment_status;
    }

    public function getHealthScore(): int
    {
        $score = 100;

        // Deduct for payment issues
        if ($this->failed_payment_attempts > 0) {
            $score -= ($this->failed_payment_attempts * 10);
        }

        // Deduct for cancellation
        if ($this->isCancelled()) {
            $score -= 50;
        }

        // Deduct for suspension
        if ($this->isSuspended()) {
            $score -= 80;
        }

        // Bonus for renewals
        if ($this->renewal_count > 0) {
            $score += min(20, $this->renewal_count * 2);
        }

        return max(0, min(100, $score));
    }
}