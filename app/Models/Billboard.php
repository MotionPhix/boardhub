<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Billboard extends Model implements HasMedia
{
  use HasFactory;
  use InteractsWithMedia;
  use SoftDeletes;

  protected $fillable = [
    'name',
    'location',
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

  public function contracts(): HasMany
  {
    return $this->hasMany(Contract::class);
  }

  public function location(): BelongsTo
  {
    return $this->belongsTo(Location::class);
  }
}
