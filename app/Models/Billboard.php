<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billboard extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'code',
        // Old schema fields (still exist in SQLite)
        'location_id',
        'base_price',
        'currency_code',
        'physical_status',
        'latitude',
        'longitude',
        'site',
        'is_active',
        'meta_data',
        // New schema fields
        'location',
        'size',
        'price',
        'status',
        'description',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    // Status constants for physical status
    public const PHYSICAL_STATUS_GOOD = 'good';

    public const PHYSICAL_STATUS_FAIR = 'fair';

    public const PHYSICAL_STATUS_NEEDS_REPAIR = 'needs_repair';

    // Legacy status constants for backward compatibility
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_MAINTENANCE = 'maintenance';

    public static function getPhysicalStatuses(): array
    {
        return [
            self::PHYSICAL_STATUS_GOOD => 'Good Condition',
            self::PHYSICAL_STATUS_FAIR => 'Fair Condition',
            self::PHYSICAL_STATUS_NEEDS_REPAIR => 'Needs Repair',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_OCCUPIED => 'Occupied',
            self::STATUS_MAINTENANCE => 'Under Maintenance',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the availability status based on current bookings
     */
    public function getAvailabilityStatusAttribute(): string
    {
        $activeBooking = $this->bookings()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($activeBooking) {
            return self::STATUS_OCCUPIED;
        }

        if ($this->status === self::STATUS_MAINTENANCE) {
            return self::STATUS_MAINTENANCE;
        }

        return self::STATUS_AVAILABLE;
    }

    /**
     * Get the full location address (now just returns the location string)
     */
    public function getFullLocationAttribute(): string
    {
        return $this->location ?? 'Unknown Location';
    }

    /**
     * Scope to only available billboards
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
            ->whereDoesntHave('bookings', function ($query) {
                $query->where('status', Booking::STATUS_CONFIRMED)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            });
    }
}
