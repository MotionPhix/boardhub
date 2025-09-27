<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\NotificationSettings;
use Illuminate\Console\Command;

class TestNotification extends Command
{
  protected $signature = 'notifications:test {email?} {--type=} {--all}';
  protected $description = 'Test notifications based on user preferences';

  public function handle()
  {
    $username = $this->argument('email') ?? 'admin@example.com';
    $user = User::where('email', $username)->first();

    if (!$user) {
      $this->error("User not found with email: {$username}");
      return 1;
    }

    // Get all notification types or specific type if provided
    $types = $this->option('type')
      ? [$this->option('type')]
      : array_keys(NotificationSettings::getNotificationTypes());

    foreach ($types as $type) {
      // Skip if user hasn't enabled this type and --all flag is not set
      if (!$this->option('all') && !$user->shouldReceiveNotification($type, 'database')) {
        $this->info("Skipping {$type} notification (not enabled by user)");
        continue;
      }

      try {
        $this->sendNotification($user, $type);
        $this->info("Sent {$type} notification successfully!");
      } catch (\Exception $e) {
        $this->error("Error sending {$type} notification: {$e->getMessage()}");
      }
    }

    $this->info("\nCheck these places:");
    $this->line('1. The custom admin notifications panel');
    $this->line('2. The database notifications table');

    return 0;
  }

  protected function sendNotification(User $user, string $type): void
  {
    $data = match ($type) {
      'contract_expiring' => [
        "type" => "contract_expiring",
        "title" => "Contract Expiring Soon",
        "message" => "Contract #CNT-2023-05 for Billboard B001 will expire in 30 days",
        "status" => "warning",
        "icon" => "clock",
        "iconColor" => "warning",
        "contract_id" => 1,
        "contract_number" => "CNT-2023-05",
        "expiry_date" => "2025-06-19 00:00:00"
      ],
      'contract_renewal' => [
        "type" => "contract_renewal",
        "title" => "Contract Renewal Available",
        "message" => "Contract #CNT-2023-06 is eligible for renewal",
        "status" => "info",
        "icon" => "document-check",
        "iconColor" => "primary",
        "contract_id" => 2,
        "contract_number" => "CNT-2023-06",
        "renewal_by" => "2025-05-26 00:00:00"
      ],
      'billboard_maintenance' => [
        "type" => "billboard_maintenance",
        "title" => "New Billboard Added",
        "message" => "A new billboard has been added to the system at Lilongwe City Centre",
        "status" => "success",
        "icon" => "plus-circle",
        "iconColor" => "success",
        "billboard_id" => 1,
        "billboard_code" => "B001",
        "location" => "Lilongwe City Centre"
      ],
      'billboard_availability' => [
        "type" => "billboard_availability",
        "title" => "Billboard Available for Booking",
        "message" => "Billboard B002 at Blantyre CBD is now available for booking",
        "status" => "info",
        "icon" => "check-badge",
        "iconColor" => "primary",
        "billboard_id" => 2,
        "billboard_code" => "B002",
        "location" => "Blantyre CBD",
        "available_from" => now()->toDateTimeString()
      ],
      default => throw new \InvalidArgumentException("Unknown notification type: {$type}")
    };

    $user->notify(new \App\Notifications\TestNotification($data));
  }
}
