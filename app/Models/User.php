<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements FilamentUser
{
  use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, HasPermissions;

  protected $fillable = [
    'name',
    'email',
    'password',
    'avatar',
    'phone',
    'bio',
    'is_active',
    'email_verified_at',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Clear notification-related cache for the user.
   */
  public function clearNotificationCache(): void
  {
    $cacheKeys = [
      "user_{$this->id}_unread_notifications_count",
      "user_{$this->id}_recent_notifications",
    ];

    // Clear notification settings cache for each type and channel
    foreach (NotificationSettings::getNotificationTypes() as $type => $_) {
      foreach (NotificationSettings::getNotificationChannels() as $channel => $_) {
        $cacheKeys[] = "user_{$this->id}_notification_setting_{$type}_{$channel}";
      }
      $cacheKeys[] = "user_{$this->id}_enabled_channels_{$type}";
    }

    Cache::deleteMultiple($cacheKeys);
  }

  /**
   * Get all notification settings for the user.
   */
  public function notificationSettings(): HasMany
  {
    return $this->hasMany(NotificationSettings::class);
  }

  /**
   * Get unread notifications count with caching.
   */
  public function getUnreadNotificationsCountAttribute(): int
  {
    $cacheKey = "user_{$this->id}_unread_notifications_count";

    return Cache::remember($cacheKey, now()->addMinutes(5), function () {
      return $this->unreadNotifications()->count();
    });
  }

  public function loginActivities(): HasMany
  {
    return $this->hasMany(\App\Models\UserLoginActivity::class);
  }

  /**
   * Get recent notifications with caching.
   */
  public function getRecentNotificationsAttribute(): Collection
  {
    $cacheKey = "user_{$this->id}_recent_notifications";

    return Cache::remember($cacheKey, now()->addMinutes(5), function () {
      return $this->notifications()
        ->latest()
        ->take(5)
        ->get();
    });
  }

  /**
   * Check if user should receive a specific type of notification through a channel.
   */
  public function shouldReceiveNotification(string $type, string $channel): bool
  {
    $cacheKey = "user_{$this->id}_notification_setting_{$type}_{$channel}";

    return Cache::remember($cacheKey, now()->addHour(), function () use ($type, $channel) {
      return $this->notificationSettings()
        ->where('type', $type)
        ->where('channel', $channel)
        ->where('is_enabled', true)
        ->exists();
    });
  }

  /**
   * Get the email address for notifications.
   */
  public function routeNotificationForMail(): string
  {
    return $this->email;
  }

  /**
   * Get the Pusher channel name for the user's private notifications.
   */
  public function routeNotificationForBroadcast(): string
  {
    return 'users.' . $this->id;
  }

  /**
   * Mark multiple notifications as read.
   */
  public function markNotificationsAsRead(array $ids = null): void
  {
    $query = $this->unreadNotifications();

    if ($ids) {
      $query->whereIn('id', $ids);
    }

    $query->update(['read_at' => now()]);

    // Clear the cache
    $this->clearNotificationCache();
  }

  /**
   * Clear all notifications for the user.
   */
  public function clearAllNotifications(): void
  {
    $this->notifications()->delete();
    $this->clearNotificationCache();
  }

  /**
   * Get grouped notifications by date.
   */
  public function getGroupedNotifications(int $days = 30): Collection
  {
    return $this->notifications()
      ->where('created_at', '>=', now()->subDays($days))
      ->latest()
      ->get()
      ->groupBy(function ($notification) {
        $date = $notification->created_at;

        if ($date->isToday()) {
          return 'Today';
        }

        if ($date->isYesterday()) {
          return 'Yesterday';
        }

        if ($date->isCurrentWeek()) {
          return 'This Week';
        }

        if ($date->isLastWeek()) {
          return 'Last Week';
        }

        return 'Older';
      });
  }

  /**
   * Get notifications by type.
   */
  public function getNotificationsByType(string $type, int $limit = 10): Collection
  {
    return $this->notifications()
      ->whereJsonContains('data->type', $type)
      ->latest()
      ->take($limit)
      ->get();
  }

  /**
   * Get enabled notification channels for a type.
   */
  public function getEnabledChannels(string $type): array
  {
    $cacheKey = "user_{$this->id}_enabled_channels_{$type}";

    return Cache::remember($cacheKey, now()->addHour(), function () use ($type) {
      return $this->notificationSettings()
        ->where('type', $type)
        ->where('is_enabled', true)
        ->pluck('channel')
        ->toArray();
    });
  }

  /**
   * Custom method to handle notification preferences update.
   */
  public function updateNotificationPreferences(array $preferences): void
  {
    foreach ($preferences as $key => $isEnabled) {
      [$type, $channel] = explode('_', $key, 2);

      $this->notificationSettings()->updateOrCreate(
        [
          'type' => $type,
          'channel' => $channel,
        ],
        [
          'is_enabled' => $isEnabled,
        ]
      );
    }

    $this->clearNotificationCache();
  }

  public function canAccessPanel(Panel $panel): bool
  {
    return $this->hasVerifiedEmail() &&
      $this->is_active &&
      $this->roles->count() > 0;
  }

  public function canAccessResource(string $resource): bool
  {
    $model = str_replace('Resource', '', class_basename($resource));
    return $this->can("view_any_" . strtolower($model));
  }

  public function canImpersonate(): bool
  {
    return $this->is_admin;
  }

  public function getFilamentShieldPermissions(): array
  {
    return $this->getAllPermissions()->pluck('name')->toArray();
  }

  /**
   * Boot the model.
   */
  protected static function boot()
  {
    parent::boot();

    // Clear cache when a notification is marked as read
    static::updated(function ($user) {
      if ($user->isDirty('read_at')) {
        Cache::forget("user_{$user->id}_unread_notifications_count");
        Cache::forget("user_{$user->id}_recent_notifications");
      }
    });
  }
}
