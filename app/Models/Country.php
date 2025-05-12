<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
  protected $fillable = [
    'code',
    'name',
    'is_active',
    'is_default',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'is_default' => 'boolean',
  ];

  public function locations(): HasMany
  {
    return $this->hasMany(Location::class, 'country', 'code');
  }

  public function cities(): HasMany
  {
    return $this->hasMany(City::class, 'country_code', 'code');
  }
}
