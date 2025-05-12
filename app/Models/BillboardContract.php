<?php

namespace App\Models;

use App\Traits\HasMoney;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BillboardContract extends Pivot
{
  use HasMoney;

  protected $table = 'billboard_contract';

  protected $fillable = [
    'billboard_id',
    'contract_id',
    'base_price',
    'discount_amount',
    'final_price',
    'booking_status',
    'notes',
  ];

  protected $casts = [
    'base_price' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'final_price' => 'decimal:2',
  ];

  public function billboard()
  {
    return $this->belongsTo(Billboard::class);
  }

  public function contract()
  {
    return $this->belongsTo(Contract::class);
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($pivot) {
      // If base_price is not set, get it from the billboard
      if (!$pivot->base_price) {
        $pivot->base_price = Billboard::find($pivot->billboard_id)->base_price;
      }

      // Calculate final price
      $pivot->final_price = $pivot->base_price - ($pivot->discount_amount ?? 0);
    });

    static::updating(function ($pivot) {
      if ($pivot->isDirty(['base_price', 'discount_amount'])) {
        $pivot->final_price = $pivot->base_price - ($pivot->discount_amount ?? 0);
      }
    });
  }
}
