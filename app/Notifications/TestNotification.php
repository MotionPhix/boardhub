<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements ShouldQueue
{
  use Queueable;

  protected array $data;

  public function __construct(array $data = [])
  {
    $this->data = $data;
  }

  public function via($notifiable): array
  {
    // Get enabled channels for this notification type
    $type = $this->data['type'] ?? 'general';
    $channels = [];

    if ($notifiable->shouldReceiveNotification($type, 'database')) {
      $channels[] = 'database';
    }

    if ($notifiable->shouldReceiveNotification($type, 'broadcast')) {
      $channels[] = 'broadcast';
    }

    return $channels;
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'id' => $this->id,
      ...$this->data,
      'time' => now()->toIso8601String(),
      'read' => false,
    ]);
  }

  public function toDatabase($notifiable): array
  {
    return [
      ...$this->data,
      'time' => now()->toIso8601String(),
    ];
  }
}
