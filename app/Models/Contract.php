<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'client_id',
    'contract_number',
    'start_date',
    'end_date',
    'total_amount',
    'status',
    'notes',
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'total_amount' => 'decimal:2',
  ];

  public function billboards(): BelongsToMany
  {
    return $this->belongsToMany(Billboard::class)
      ->withPivot(['price', 'start_date', 'end_date', 'status', 'notes'])
      ->withTimestamps();
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($contract) {
      // Generate a unique contract number if not set
      if (!$contract->contract_number) {
        $contract->contract_number = 'CNT-' . date('Y') . '-' . str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT);
      }
    });
  }
}
