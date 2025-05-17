<?php

namespace App\Models;

use App\Enums\BookingStatus;
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
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Billboard extends Model implements HasMedia
{
  use HasFactory, InteractsWithMedia, SoftDeletes, HasUuid, HasMoney;

  protected $fillable = [
    'name',
    'code',
    'location_id',
    'size',
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
  const PHYSICAL_STATUS_REMOVED = 'removed';
  const PHYSICAL_STATUS_STOLEN = 'stolen';

  /**
   * Get the contracts associated with the billboard
   */
  public function contracts(): BelongsToMany
  {
    return $this->belongsToMany(Contract::class, 'billboard_contract')
      ->using(BillboardContract::class)
      ->withPivot([
        'billboard_base_price',
        'billboard_discount_amount',
        'billboard_final_price',
        'booking_status',
        'notes'
      ])
      ->withTimestamps();
  }

  /**
   * Get the location that owns the billboard
   */
  public function location(): BelongsTo
  {
    return $this->belongsTo(Location::class);
  }

  /**
   * Get the physical statuses with their labels
   */
  public static function getPhysicalStatuses(): array
  {
    return [
      self::PHYSICAL_STATUS_OPERATIONAL => 'Operational',
      self::PHYSICAL_STATUS_MAINTENANCE => 'Under Maintenance',
      self::PHYSICAL_STATUS_DAMAGED => 'Damaged',
      self::PHYSICAL_STATUS_REMOVED => 'Removed',
      self::PHYSICAL_STATUS_STOLEN => 'Stolen',
    ];
  }

  /**
   * Get/Set the physical status (ensuring lowercase)
   */
  protected function physicalStatus(): Attribute
  {
    return Attribute::make(
      get: fn (string $value) => strtolower($value),
      set: fn (string $value) => strtolower($value)
    );
  }

  /**
   * Get the current contract for the billboard
   */
  public function currentContract(): Attribute
  {
    return Attribute::make(
      get: fn () => $this->contracts()
        ->wherePivot('booking_status', 'in_use')
        ->first()
    );
  }

  /**
   * Get the availability status of the billboard
   */
  public function availabilityStatus(): Attribute
  {
    return Attribute::make(
      get: function () {
        if ($this->physical_status !== self::PHYSICAL_STATUS_OPERATIONAL) {
          return $this->physical_status;
        }

        return $this->current_contract ? 'occupied' : 'available';
      }
    );
  }

  /**
   * Get the formatted base price
   */
  public function formattedBasePrice(): Attribute
  {
    return Attribute::make(
      get: fn () => $this->formatMoney($this->base_price)
    );
  }

  /**
   * Register media collections and conversions
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('billboard_images')
      ->useDisk('media')
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
      ->registerMediaConversions(function (Media $media = null) {
        // Thumbnail for admin panel
        $this->addMediaConversion('thumb')
          ->fit(Fit::Contain, 300, 200)
          ->optimize()
          ->keepOriginalImageFormat()
          ->nonQueued();

        // Preview size
        $this->addMediaConversion('preview')
          ->fit(Fit::Contain, 800, 450)
          ->optimize()
          ->keepOriginalImageFormat()
          ->nonQueued();

        // Full size with optimization
        $this->addMediaConversion('full')
          ->fit(Fit::Contain, 1920, 1080)
          ->optimize()
          ->keepOriginalImageFormat()
          ->withResponsiveImages()
          ->nonQueued();

        // Social media sharing optimized
        $this->addMediaConversion('social')
          ->fit(Fit::Contain, 1200, 630)
          ->optimize()
          ->keepOriginalImageFormat()
          ->nonQueued();
      });
  }

  /**
   * Check if the billboard is available for booking
   */
  public function isAvailable(): bool
  {
    return !$this->contracts()
      ->wherePivot('booking_status', BookingStatus::IN_USE->value)
      ->whereHas('contract', function ($query) {
        $query->whereIn('agreement_status', ['draft', 'active'])
          ->where(function ($q) {
            $q->whereDate('end_date', '>=', now())
              ->orWhereNull('end_date');
          });
      })
      ->exists();
  }

  #[Scope]
  protected function available(Builder $query): void
  {
    $query->whereDoesntHave('contracts', function ($query) {
      $query->wherePivot('booking_status', BookingStatus::IN_USE->value)
        ->whereHas('contract', function ($query) {
          $query->whereIn('agreement_status', ['draft', 'active'])
            ->where(function ($q) {
              $q->whereDate('end_date', '>=', now())
                ->orWhereNull('end_date');
            });
        });
    });
  }

  #[Scope]
  protected function active(Builder $query)
  {
    $query->where('is_active', true);
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

  public static function generateBillboardCode(City $city): string
  {
    $settings = Settings::firstOrCreate();

    $prefix = $settings->billboard_code_prefix;
    $separator = $settings->billboard_code_separator;
    $counterLength = $settings->billboard_code_counter_length;

    // Get the last billboard number for this city
    $lastBillboard = static::where('code', 'like', "{$prefix}{$separator}{$city->code}{$separator}%")
      ->orderByRaw('CONVERT(SUBSTRING_INDEX(code, ?, -1), SIGNED) DESC', [$separator])
      ->first();

    $counter = 1;

    if ($lastBillboard) {
      $parts = explode($separator, $lastBillboard->code);
      $counter = (int)end($parts) + 1;
    }

    return sprintf(
      '%s%s%s%s%0' . $counterLength . 'd',
      $prefix,
      $separator,
      $city->code,
      $separator,
      $counter
    );
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($billboard) {

      if (!$billboard->currency_code) {
        $billboard->currency_code = Currency::getDefault()->code ?? 'MWK';
      }

      if (!$billboard->code) {
        $billboard->code = static::generateBillboardCode($billboard->location->city);
      }

      // Ensure physical_status is set to a valid value
      if (!$billboard->physical_status) {
        $billboard->physical_status = self::PHYSICAL_STATUS_OPERATIONAL;
      }

    });

    static::updating(function ($billboard) {
      // Prevent changing is_active to true if billboard is in use
      if ($billboard->isDirty('is_active') && $billboard->is_active) {
        if (!$billboard->isAvailable()) {
          throw new \Exception("Cannot activate billboard while it is in use.");
        }
      }
    });

  }
}
