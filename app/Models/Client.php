<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Client extends Model implements HasMedia
{
  use HasFactory, SoftDeletes, InteractsWithMedia;

  protected $fillable = [
    'name',
    'email',
    'phone',
    'company',
    'address',
  ];

  public function contracts(): HasMany
  {
    return $this->hasMany(Contract::class);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('documents')
      ->useDisk('public');
  }

  public function getTotalContractsValueAttribute()
  {
    return $this->contracts()->sum('total_amount');
  }

  public function getActiveContractsCountAttribute()
  {
    return $this->contracts()
      ->where('status', 'active')
      ->where('end_date', '>=', now())
      ->count();
  }
}
