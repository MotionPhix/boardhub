<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
  protected $fillable = [
    'code',
    'name',
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

  public function cities(): HasMany
  {
    return $this->hasMany(City::class, 'state_code', 'code');
  }
}
