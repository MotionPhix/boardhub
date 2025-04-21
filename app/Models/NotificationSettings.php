<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSettings extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'email_notifications',
    'database_notifications',
    'notification_thresholds',
  ];

  protected $casts = [
    'email_notifications' => 'boolean',
    'database_notifications' => 'boolean',
    'notification_thresholds' => 'array',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
