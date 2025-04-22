<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
  use HasFactory, HasUuid;

  protected $fillable = [
    'name',
    'slug',
    'description',
    'city',
    'state',
    'country',
    'postal_code',
    'latitude',
    'longitude',
    'is_active',
  ];

  protected $casts = [
    'latitude' => 'decimal:8',
    'longitude' => 'decimal:8',
    'is_active' => 'boolean',
  ];

  public function billboards(): HasMany
  {
    return $this->hasMany(Billboard::class);
  }
}
