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
    'city',
    'state',
    'country',
    'postal_code',
    'latitude',
    'longitude',
  ];

  public function billboards(): HasMany
  {
    return $this->hasMany(Billboard::class);
  }
}
