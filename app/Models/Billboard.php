<?php

namespace App\Models;

use App\Traits\HasMoney;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
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
    'base_price',
    'physical_status',
    'description',
    'latitude',
    'longitude',
    'currency_code',
    'is_active',
    'meta_data',
    'site'
  ];

  protected $casts = [
    'base_price' => 'decimal:2',
    'is_active' => 'boolean',
    'meta_data' => 'json',
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
  public function formattedBasePrice(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->formatMoney($this->base_price)
    );
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('billboard-images')
      ->useDisk('public')
      ->registerMediaConversions(function () {
        $this
          ->addMediaConversion('thumb')
          ->fit(Fit::Crop, 300, 200)
          ->sharpen(10)
          ->optimize();

        $this
          ->addMediaConversion('preview')
          ->fit(Fit::Contain, 800, 600)
          ->sharpen(10)
          ->optimize();
      });
  }

  #[Scope]
  protected function active(Builder $query): void
  {
    $query->where('is_active', true);
  }

  #[Scope]
  protected function available(Builder $query, $startDate, $endDate): void
  {
    $query->whereDoesntHave('contracts', function ($query) use ($startDate, $endDate) {
      $query->where('booking_status', 'in_use')
        ->where(function ($q) use ($startDate, $endDate) {
          $q->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function ($q) use ($startDate, $endDate) {
              $q->where('start_date', '<=', $startDate)
                ->where('end_date', '>=', $endDate);
            });
        });
    });
  }

  #[Scope]
  protected function withinRadius(Builder $query, $lat, $lng, $radius): void
  {
    $query->whereRaw("
      ST_Distance_Sphere(
        point(longitude, latitude),
        point(?, ?)
      ) <= ?
    ", [$lng, $lat, $radius * 1000]);
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($billboard) {
      if (!$billboard->currency_code) {
        $billboard->currency_code = Settings::getDefaultCurrency()['currency_code'] ?? 'MWK';
      }

      // Generate billboard code
      $format = Settings::get('billboard_code_format');
      $location = Location::find($billboard->location_id);

      // Get city code from location
      $cityCode = $location->city_code;

      // Get the last billboard number
      $lastBillboard = static::orderByDesc('id')->first();
      $counter = $lastBillboard
        ? (int) substr($lastBillboard->code, -$format['counter_length']) + 1
        : 1;

      // Generate the code: PREFIX-CITYCODE-NUMBER
      $billboard->code = implode($format['separator'], [
        $format['prefix'],
        $cityCode,
        str_pad($counter, $format['counter_length'], '0', STR_PAD_LEFT)
      ]);

    });

  }
}
