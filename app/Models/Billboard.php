<?php

namespace App\Models;

use App\Traits\HasMoney;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Billboard extends Model implements HasMedia
{
  use HasFactory, InteractsWithMedia, SoftDeletes, HasUuid, HasMoney;

  protected $fillable = [
    'name',
    'location_id',
    'size',
    'type',
    'base_price', // Changed from price to base_price for consistency
    'physical_status',
    'description',
    'latitude',
    'longitude',
  ];

  protected $casts = [
    'base_price' => 'decimal:2',
  ];

  // Define possible physical statuses as constants
  const PHYSICAL_STATUS_OPERATIONAL = 'operational';
  const PHYSICAL_STATUS_MAINTENANCE = 'maintenance';
  const PHYSICAL_STATUS_DAMAGED = 'damaged';

  public function contracts(): BelongsToMany
  {
    return $this->belongsToMany(Contract::class, 'billboard_contract')
      ->using(BillboardContract::class)
      ->withPivot([
        'base_price',
        'discount_amount',
        'final_price',
        'booking_status',
        'notes'
      ])
      ->withTimestamps();
  }

  public function location(): BelongsTo
  {
    return $this->belongsTo(Location::class);
  }

  public static function getPhysicalStatuses(): array
  {
    return [
      self::PHYSICAL_STATUS_OPERATIONAL => 'Operational',
      self::PHYSICAL_STATUS_MAINTENANCE => 'Under Maintenance',
      self::PHYSICAL_STATUS_DAMAGED => 'Damaged',
    ];
  }

  public function getCurrentContractAttribute()
  {
    return $this->contracts()
      ->wherePivot('booking_status', 'in_use')
      ->first();
  }

  public function getAvailabilityStatusAttribute(): string
  {
    if ($this->physical_status !== self::PHYSICAL_STATUS_OPERATIONAL) {
      return $this->physical_status;
    }

    return $this->current_contract ? 'occupied' : 'available';
  }

  // Price-related methods
  public function getFormattedBasePriceAttribute(): string
  {
    return $this->formatMoney($this->base_price);
  }
}
