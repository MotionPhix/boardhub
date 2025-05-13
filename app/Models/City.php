<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
  protected $fillable = [
    'name',
    'code',
    'state_code',
    'country_code',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function country(): BelongsTo
  {
    return $this->belongsTo(Country::class, 'country_code', 'code');
  }

  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class, 'state_code', 'code');
  }

  public function locations(): HasMany
  {
    return $this->hasMany(Location::class, 'city', 'code');
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($city) {
      // If no code is provided, generate one from the name
      if (empty($city->code)) {
        $city->code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]+/', '', $city->name), 0, 3));
      }
    });
  }
}
