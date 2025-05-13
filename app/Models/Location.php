<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
  use HasFactory, HasUuid;

  protected $fillable = [
    'name',
    'description',
    'city_code',
    'state_code',
    'country_code',
    'is_active'
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function billboards(): HasMany
  {
    return $this->hasMany(Billboard::class);
  }

  public function city(): BelongsTo
  {
    return $this->belongsTo(City::class, 'city_code', 'code');
  }

  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class, 'state_code', 'code');
  }

  public function country(): BelongsTo
  {
    return $this->belongsTo(Country::class, 'country_code', 'code');
  }

  /**
   * Get the full address of the location.
   */
  public function getFullAddressAttribute(): string
  {
    return implode(', ', array_filter([
      $this->getAttribute('city')?->name,
      $this->getAttribute('state')?->name,
      $this->getAttribute('country')?->name,
    ]));
  }
}
