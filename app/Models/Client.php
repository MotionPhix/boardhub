<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Client extends Model implements HasMedia
{
  use HasFactory, SoftDeletes, InteractsWithMedia, HasUuid;

  protected $fillable = [
    'name',
    'email',
    'phone',
    'company',
    'street',
    'city',
    'state',
    'country'
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

  public function totalContractsValue(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->contracts()
        ->where('agreement_status', 'active')
        ->sum('total_amount')
    );
  }

  public function activeContractsCount(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->contracts()
        ->where('agreement_status', 'active')
        ->where('end_date', '>=', now())
        ->count(),
    );
  }

  public function address(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->street . ', ' . $this->city . ', ' . $this->state . ', ' . $this->country
    );
  }
}
