<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'billing_plan_id',
        'status',
        'payment_status',
        'paychangu_subscription_id',
        'paychangu_customer_id',
        'amount',
        'currency',
        'interval',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'cancelled_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->current_period_end > now();
    }

    public function isTrialActive(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->current_period_end < now() ||
               ($this->status === 'trial' && $this->trial_ends_at < now());
    }

    public function daysUntilExpiration(): int
    {
        $expirationDate = $this->status === 'trial'
            ? $this->trial_ends_at
            : $this->current_period_end;

        return max(0, now()->diffInDays($expirationDate, false));
    }
}
