<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersion extends Model
{
  protected $fillable = [
    'contract_id',
    'version',
    'content',
    'metadata',
  ];

  protected $casts = [
    'metadata' => 'array',
  ];

  public function contract(): BelongsTo
  {
    return $this->belongsTo(Contract::class);
  }
}
