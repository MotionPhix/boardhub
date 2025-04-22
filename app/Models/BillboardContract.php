<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BillboardContract extends Pivot
{
  protected $table = 'billboard_contract';

  protected $casts = [
    'price' => 'decimal:2',
  ];

  protected $fillable = [
    'billboard_id',
    'contract_id',
    'price',
    'booking_status',
    'notes',
  ];
}
