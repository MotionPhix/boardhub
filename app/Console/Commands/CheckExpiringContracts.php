<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpirationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckExpiringContracts extends Command
{
  protected $signature = 'contracts:check-expiring';
  protected $description = 'Check for contracts that are approaching expiration';

  public function handle()
  {
    // Define notification thresholds (days before expiration)
    $thresholds = [30, 14, 7, 3, 1];

    foreach ($thresholds as $days) {
      $contracts = Contract::query()
        ->where('status', 'active')
        ->whereDate('end_date', now()->addDays($days)->startOfDay())
        ->with(['client', 'billboards'])
        ->get();

      if ($contracts->isEmpty()) {
        $this->info("No contracts expiring in $days days.");
        continue;
      }

      foreach ($contracts as $contract) {
        // Get users who should be notified (admins and managers)
        $users = User::whereHas('roles', function ($query) {
          $query->whereIn('name', ['admin', 'manager']);
        })->get();

        // Send notifications
        Notification::send($users, new ContractExpirationNotification($contract, $days));

        $this->info("Sent notification for contract {$contract->contract_number} expiring in $days days.");
      }
    }

    return self::SUCCESS;
  }
}
