<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'client_id',
        'provider',
        'amount',
        'currency',
        'phone_number',
        'reference',
        'external_id',
        'status',
        'failure_reason',
        'provider_response',
        'metadata',
        'initiated_at',
        'completed_at',
        'failed_at',
        'status_checked_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'status_checked_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    // Provider constants
    public const PROVIDER_AIRTEL_MONEY = 'airtel_money';
    public const PROVIDER_TNM_MPAMBA = 'tnm_mpamba';
    public const PROVIDER_BANK_TRANSFER = 'bank_transfer';
    public const PROVIDER_CARD = 'card';
    public const PROVIDER_PAYCHANGU = 'paychangu';

    // Accessor methods
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'green',
            self::STATUS_PROCESSING => 'yellow',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'blue',
            default => 'gray',
        };
    }

    public function getProviderDisplayNameAttribute(): string
    {
        return match ($this->provider) {
            self::PROVIDER_AIRTEL_MONEY => 'Airtel Money',
            self::PROVIDER_TNM_MPAMBA => 'TNM Mpamba',
            self::PROVIDER_BANK_TRANSFER => 'Bank Transfer',
            self::PROVIDER_CARD => 'Card Payment',
            self::PROVIDER_PAYCHANGU => 'PayChangu',
            default => ucfirst(str_replace('_', ' ', $this->provider)),
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'MWK ' . number_format($this->amount, 2);
    }

    public function getFormattedPhoneNumberAttribute(): string
    {
        $phone = $this->phone_number;

        if (str_starts_with($phone, '265')) {
            return '+' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 2) . ' ' .
                   substr($phone, 5, 3) . ' ' . substr($phone, 8);
        }

        return $phone;
    }

    // Query scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBeRefunded(): bool
    {
        return $this->isCompleted() && $this->created_at->greaterThan(now()->subDays(30));
    }

    public function getTimeSinceInitiated(): string
    {
        if (!$this->initiated_at) {
            return 'Unknown';
        }

        return $this->initiated_at->diffForHumans();
    }

    public function getDuration(): ?int
    {
        if ($this->initiated_at && $this->completed_at) {
            return $this->initiated_at->diffInSeconds($this->completed_at);
        }

        return null;
    }

    // Boot method for automatic UUID generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->initiated_at) {
                $payment->initiated_at = now();
            }
        });

        static::updating(function ($payment) {
            // Automatically set completed_at when status changes to completed
            if ($payment->isDirty('status')) {
                if ($payment->status === self::STATUS_COMPLETED && !$payment->completed_at) {
                    $payment->completed_at = now();
                } elseif ($payment->status === self::STATUS_FAILED && !$payment->failed_at) {
                    $payment->failed_at = now();
                }
            }
        });
    }
}