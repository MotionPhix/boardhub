<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
  protected $signature = 'notifications:test {email}';
  protected $description = 'Test the notification system';

  public function handle()
  {
    $email = $this->argument('email');
    $user = User::where('email', $email)->first();

    if (!$user) {
      $this->error("User not found with email: {$email}");
      return 1;
    }

    try {
      // Send a Filament notification
      Notification::make()
        ->title('Test Notification')
        ->body('This is a test notification sent at ' . now())
        ->icon('heroicon-o-bell')
        ->iconColor('success')
        ->sendToDatabase($user);

      // Trigger real-time broadcast
      $user->notify(new \App\Notifications\TestNotification());

      $this->info('Test notification sent successfully!');
      $this->info('Check these places:');
      $this->line('1. Your email inbox');
      $this->line('2. The notifications bell icon in the app');
      $this->line('3. The database notifications table');

      return 0;

    } catch (\Exception $e) {
      $this->error("Error sending notification: {$e->getMessage()}");
      return 1;
    }
  }
}
