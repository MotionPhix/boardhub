<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginActivity extends Model
{
  protected $fillable = [
    'user_id',
    'ip_address',
    'user_agent',
    'location',
    'login_at',
    'logout_at',
    'login_successful',
    'login_type',
    'details',
  ];

  protected $casts = [
    'login_at' => 'datetime',
    'logout_at' => 'datetime',
    'login_successful' => 'boolean',
    'details' => 'array',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
