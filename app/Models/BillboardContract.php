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
    'billboard_base_price',
    'billboard_discount_amount',
    'billboard_final_price',
    'booking_status',
    'notes',
  ];

  protected $casts = [
    'billboard_base_price' => 'decimal:2',
    'billboard_discount_amount' => 'decimal:2',
    'billboard_final_price' => 'decimal:2',
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
      // If billboard_base_price is not set, get it from the billboard
      if (!$pivot->billboard_base_price) {
        $pivot->billboard_base_price = Billboard::find($pivot->billboard_id)->base_price;
      }

      // Calculate billboard_final_price
      $pivot->billboard_final_price = $pivot->billboard_base_price - ($pivot->billboard_discount_amount ?? 0);
    });

    static::updating(function ($pivot) {
      if ($pivot->isDirty(['billboard_base_price', 'billboard_discount_amount'])) {
        $pivot->billboard_final_price = $pivot->billboard_base_price - ($pivot->billboard_discount_amount ?? 0);
      }
    });
  }
}
