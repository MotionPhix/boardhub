<?php

namespace App\Models;

use App\States\BookingState;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'billboard_id',
        'client_id',
        'start_date',
        'end_date',
        'requested_price',
        'final_price',
        'campaign_details',
        'status',
        'confirmed_at',
        'rejected_at',
        'rejection_reason',
        'price_negotiations',
        'status_history',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'requested_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'price_negotiations' => 'array',
        'status_history' => 'array',
    ];

    // Status constants
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public function billboard(): BelongsTo
    {
        return $this->belongsTo(Billboard::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the booking state for event sourcing
     */
    public function getBookingState(): BookingState
    {
        return BookingState::load($this->id);
    }

    /**
     * Calculate duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if booking is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->start_date) &&
               $now->lessThanOrEqualTo($this->end_date);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'yellow',
            self::STATUS_CONFIRMED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_COMPLETED => 'blue',
            default => 'gray',
        };
    }
}
