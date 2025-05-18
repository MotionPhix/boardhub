<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
  use Queueable;

  protected function getEnabledChannels($notifiable, string $type): array
  {
    return $notifiable->notificationSettings()
      ->where('type', $type)
      ->where('is_enabled', true)
      ->pluck('channel')
      ->toArray();
  }

  protected function shouldSendNotification($notifiable, string $type): bool
  {
    return $notifiable->notificationSettings()
      ->where('type', $type)
      ->where('is_enabled', true)
      ->exists();
  }

  protected function getTemplate(string $template, string $urgency = null, array $data = []): array
  {
    return NotificationTemplate::get($template, $urgency, $data);
  }

  protected function getNotifiablePreferences($notifiable, string $type): array
  {
    return $notifiable->notificationSettings()
      ->where('type', $type)
      ->where('is_enabled', true)
      ->pluck('channel', 'channel')
      ->toArray();
  }
}
