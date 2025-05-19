<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestNotification extends Command
{
  protected $signature = 'notifications:test {email?}';
  protected $description = 'Test the notification system with different notification types';

  public function handle()
  {
    $username = $this->argument('email') ?? 'admin@example.com';
    $user = User::where('email', $username)->first();

    if (!$user) {
      $this->error("User not found with email: {$username}");
      return 1;
    }

    try {
      // Contract Expiry Notification
      $user->notify(new \App\Notifications\TestNotification([
        "type" => "contract_expiring",
        "title" => "Contract Expiring Soon",
        "message" => "Contract #CNT-2023-05 for Billboard B001 will expire in 30 days",
        "status" => "warning",
        "icon" => "heroicon-o-clock",
        "iconColor" => "warning",
        "contract_id" => 1,
        "contract_number" => "CNT-2023-05",
        "expiry_date" => "2025-06-19 00:00:00"
      ]));

      // Contract Renewal Notification
      $user->notify(new \App\Notifications\TestNotification([
        "type" => "contract_renewal",
        "title" => "Contract Renewal Available",
        "message" => "Contract #CNT-2023-06 is eligible for renewal",
        "status" => "info",
        "icon" => "heroicon-o-document-check",
        "iconColor" => "primary",
        "contract_id" => 2,
        "contract_number" => "CNT-2023-06",
        "renewal_by" => "2025-05-26 00:00:00"
      ]));

      // New Billboard Notification
      $user->notify(new \App\Notifications\TestNotification([
        "type" => "billboard_maintenance",
        "title" => "New Billboard Added",
        "message" => "A new billboard has been added to the system at Lilongwe City Centre",
        "status" => "success",
        "icon" => "heroicon-o-plus-circle",
        "iconColor" => "success",
        "billboard_id" => 1,
        "billboard_code" => "B001",
        "location" => "Lilongwe City Centre"
      ]));

      // Available Billboard Notification
      $user->notify(new \App\Notifications\TestNotification([
        "type" => "billboard_availability",
        "title" => "Billboard Available for Booking",
        "message" => "Billboard B002 at Blantyre CBD is now available for booking",
        "status" => "info",
        "icon" => "heroicon-o-check-badge",
        "iconColor" => "primary",
        "billboard_id" => 2,
        "billboard_code" => "B002",
        "location" => "Blantyre CBD",
        "available_from" => "2025-05-19 04:49:22"
      ]));

      $this->info('Test notifications sent successfully!');
      $this->info('Check these places:');
      $this->line('1. The notifications page in Filament');
      $this->line('2. The database notifications table');

      return 0;

    } catch (\Exception $e) {
      $this->error("Error sending notification: {$e->getMessage()}");
      return 1;
    }
  }
}
