<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersion extends Model
{
  use HasUuid;

  protected $fillable = [
    'contract_id',
    'version',
    'content',
    'metadata',
  ];

  protected $casts = [
    'version' => 'integer',
    'metadata' => 'array',
  ];

  /**
   * Get the contract that owns this version.
   */
  public function contract(): BelongsTo
  {
    return $this->belongsTo(Contract::class);
  }
}
