<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Billboard extends Model implements HasMedia
{
  use HasFactory;
  use InteractsWithMedia;
  use SoftDeletes;
  use HasUuid;

  protected $fillable = [
    'name',
    'location_id',
    'size',
    'type',
    'price',
    'status',
    'description',
    'latitude',
    'longitude',
    'available_from',
    'available_until',
  ];

  protected $casts = [
    'available_from' => 'datetime',
    'available_until' => 'datetime',
    'price' => 'decimal:2',
  ];

  public function contracts(): BelongsToMany
  {
    return $this->belongsToMany(Contract::class)
      ->withPivot(['price', 'start_date', 'end_date', 'status', 'notes'])
      ->withTimestamps();
  }

  public function location(): BelongsTo
  {
    return $this->belongsTo(Location::class);
  }

  public function getCurrentContractAttribute()
  {
    return $this->contracts()
      ->wherePivot('start_date', '<=', now())
      ->wherePivot('end_date', '>=', now())
      ->wherePivot('status', 'active')
      ->first();
  }

  public function getIsAvailableAttribute(): bool
  {
    return !$this->current_contract &&
      $this->status === 'Available' &&
      (!$this->available_until || $this->available_until->isFuture());
  }
}
