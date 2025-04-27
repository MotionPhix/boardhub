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
    'type',
    'channel',
    'is_enabled',
  ];

  protected $casts = [
    'is_enabled' => 'boolean',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public static function getNotificationTypes(): array
  {
    return [
      'contract_expiry' => 'Contract Expiry',
      'contract_renewal' => 'Contract Renewal',
      'new_contract' => 'New Contract',
      'payment_due' => 'Payment Due',
      'payment_overdue' => 'Payment Overdue',
      'billboard_maintenance' => 'Billboard Maintenance',
      'billboard_availability' => 'Billboard Availability',
    ];
  }

  public static function getNotificationChannels(): array
  {
    return [
      'email' => 'Email Notifications',
      'database' => 'In-App Notifications',
      'broadcast' => 'Push Notifications',
    ];
  }
}
