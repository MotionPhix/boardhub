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
    'city',
    'state',
    'country',
    'is_active',
    'city_code'
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
    return $this->belongsTo(City::class, 'city', 'code');
  }

  /**
   * Get the full address of the location.
   */
  public function getFullAddressAttribute(): string
  {
    return implode(', ', array_filter([
      $this->getAttribute('city'),
      $this->getAttribute('state'),
      $this->getCountryName(),
    ]));
  }

  /**
   * Get the country name from the country code.
   */
  public function getCountryName(): string
  {
    $countries = [
      'MW' => 'Malawi',
      'ZM' => 'Zambia',
      'ZW' => 'Zimbabwe',
      'MZ' => 'Mozambique',
      'TZ' => 'Tanzania',
      'ZA' => 'South Africa',
    ];

    return $countries[$this->getAttribute('country')] ?? $this->getAttribute('country');
  }
}
