<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSettingsSeeder extends Seeder
{
  public function run(): void
  {
    User::all()->each(function ($user) {
      $user->notificationSettings()->create([
        'email_notifications' => true,
        'database_notifications' => true,
        'notification_thresholds' => [30, 14, 7, 3, 1],
      ]);
    });
  }
}
