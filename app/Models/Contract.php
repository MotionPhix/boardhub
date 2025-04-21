<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
  use HasFactory;

  protected $fillable = [
    'billboard_id',
    'client_id',
    'start_date',
    'end_date',
    'price',
    'status',
    'notes',
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'price' => 'decimal:2',
  ];

  public function billboard(): BelongsTo
  {
    return $this->belongsTo(Billboard::class);
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
  }
}
