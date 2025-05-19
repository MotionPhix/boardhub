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
    $this->data = $data ?: [
      'type' => 'test',
      'title' => 'Test Notification',
      'message' => 'This is a test notification sent at ' . now(),
      'status' => 'success',
      'icon' => 'heroicon-o-bell',
      'iconColor' => 'success',
    ];
  }

  public function via($notifiable): array
  {
    return ['broadcast', 'database', 'email'];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage($this->data);
  }

  public function toDatabase($notifiable): array
  {
    return $this->data;
  }
}
