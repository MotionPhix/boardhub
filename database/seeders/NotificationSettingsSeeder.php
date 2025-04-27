<?php

namespace Database\Seeders;

use App\Models\NotificationSettings;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSettingsSeeder extends Seeder
{
  public function run(): void
  {
    $users = User::all();
    $types = array_keys(NotificationSettings::getNotificationTypes());
    $channels = array_keys(NotificationSettings::getNotificationChannels());

    foreach ($users as $user) {
      foreach ($types as $type) {
        foreach ($channels as $channel) {
          NotificationSettings::create([
            'user_id' => $user->id,
            'type' => $type,
            'channel' => $channel,
            'is_enabled' => true,
          ]);
        }
      }
    }
  }
}
