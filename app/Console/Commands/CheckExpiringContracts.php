<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpirationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckExpiringContracts extends Command
{
  protected $signature = 'contracts:check-expiring {--days=30 : Days to look ahead for expiring contracts} {--notify : Send notifications}';
  protected $description = 'Check for contracts that may need attention or renewal';

  /**
   * @var array Thresholds for notification in days
   */
  protected $thresholds = [30, 14, 7, 3, 1];

  public function handle()
  {
    $lookAheadDays = $this->option('days');
    $shouldNotify = $this->option('notify');

    $this->info("Checking contracts for the next {$lookAheadDays} days...");

    // Get active contracts with their booking durations
    $contracts = Contract::query()
      ->where('agreement_status', 'active')
      ->with(['client', 'billboards', 'media'])
      ->whereHas('billboards', function ($query) {
        $query->wherePivot('booking_status', BookingStatus::IN_USE->value);
      })
      ->get();

    if ($contracts->isEmpty()) {
      $this->info('No active contracts found.');
      return self::SUCCESS;
    }

    $contractsNeedingAttention = [];
    $now = now();

    foreach ($contracts as $contract) {
      // Get the most recent billboard booking
      $longestBooking = $contract->billboards()
        ->orderByPivot('updated_at', 'desc')
        ->first();

      if (!$longestBooking) {
        continue;
      }

      $lastUpdate = $longestBooking->pivot->updated_at;
      $daysActive = $lastUpdate->diffInDays($now);

      // Check if contract needs attention based on duration active
      if ($daysActive >= 30) {
        $contractsNeedingAttention[] = [
          'contract' => $contract,
          'days_active' => $daysActive,
          'threshold' => $this->getNotificationThreshold($daysActive),
        ];
      }
    }

    if (empty($contractsNeedingAttention)) {
      $this->info('No contracts need attention at this time.');
      return self::SUCCESS;
    }

    // Sort by days active descending
    usort($contractsNeedingAttention, fn($a, $b) => $b['days_active'] - $a['days_active']);

    // Display results
    $this->table(
      ['Contract #', 'Client', 'Days Active', 'Billboards', 'Total Amount'],
      array_map(fn($item) => [
        $item['contract']->contract_number,
        $item['contract']->client->name,
        $item['days_active'],
        $item['contract']->billboards->count(),
        number_format($item['contract']->total_amount, 2),
      ], $contractsNeedingAttention)
    );

    if ($shouldNotify) {
      $this->sendNotifications($contractsNeedingAttention);
    }

    $this->info('Contract check completed successfully.');
    return self::SUCCESS;
  }

  protected function getNotificationThreshold(int $days): int
  {
    foreach ($this->thresholds as $threshold) {
      if ($days >= $threshold) {
        return $threshold;
      }
    }

    return 30; // Default threshold
  }

  protected function sendNotifications(array $contractsNeedingAttention): void
  {
    // Get users who should be notified (admins and managers)
    $users = User::whereHas('roles', function ($query) {
      $query->whereIn('name', ['admin', 'manager']);
    })->get();

    if ($users->isEmpty()) {
      $this->warn('No users found to notify.');
      return;
    }

    foreach ($contractsNeedingAttention as $item) {
      $contract = $item['contract'];
      $daysActive = $item['days_active'];
      $threshold = $item['threshold'];

      // Send notifications
      Notification::send($users, new ContractExpirationNotification(
        contract: $contract,
        daysActive: $daysActive,
        threshold: $threshold
      ));

      $this->info("Sent notification for contract {$contract->contract_number} ({$daysActive} days active)");
    }
  }
}
