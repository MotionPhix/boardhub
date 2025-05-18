<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function via($notifiable): array
  {
    return ['broadcast', 'database'];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'id' => $this->id,
      'title' => 'Test Notification',
      'message' => 'This is a test broadcast notification sent at ' . now(),
      'status' => 'success',
      'icon' => 'heroicon-o-bell',
      'iconColor' => 'success',
    ]);
  }

  public function toDatabase($notifiable): array
  {
    return [
      'title' => 'Test Notification',
      'message' => 'This is a test database notification sent at ' . now(),
      'status' => 'success',
      'icon' => 'heroicon-o-bell',
      'iconColor' => 'success',
    ];
  }
}
