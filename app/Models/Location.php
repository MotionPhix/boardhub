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
    'description',
    'city',
    'state',
    'country',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function billboards(): HasMany
  {
    return $this->hasMany(Billboard::class);
  }

  public function getFullAddressAttribute(): string
  {
    return implode(', ', array_filter([
      $this->getAttribute('city'),
      $this->getAttribute('state'),
      $this->getAttribute('country')
    ]));
  }
}
