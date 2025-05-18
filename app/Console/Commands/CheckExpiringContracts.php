<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringContracts extends Command
{
  protected $signature = 'contracts:check-expiry
        {--days=* : Days before expiry to check (default: 30,14,7,3,1)}
        {--notify : Send notifications to users}
        {--force : Force check regardless of last run time}';

  protected $description = 'Check for contracts nearing expiry and optionally send notifications';

  protected array $defaultDays = [30, 14, 7, 3, 1];

  public function handle()
  {
    $this->info('Starting contract expiry check...');

    $daysToCheck = $this->getDaysToCheck();
    $shouldNotify = $this->option('notify');
    $force = $this->option('force');

    // Check if we should run based on last execution time
    if (!$force && !$this->shouldRun()) {
      $this->info('Command was recently run. Use --force to override.');
      return;
    }

    $now = Carbon::now();
    $totalContracts = 0;
    $notificationsSent = 0;

    foreach ($daysToCheck as $days) {
      $expiryDate = $now->copy()->addDays($days)->startOfDay();

      $contracts = Contract::query()
        ->with(['billboards', 'client', 'users'])
        ->whereDate('end_date', $expiryDate->toDateString())
        ->where('agreement_status', 'active')
        ->get();

      $count = $contracts->count();
      $totalContracts += $count;

      $this->info("Found {$count} contracts expiring in {$days} days.");

      if ($count > 0 && $shouldNotify) {
        foreach ($contracts as $contract) {
          $this->processContract($contract, $days, $notificationsSent);
        }
      }
    }

    // Log the execution
    $this->logExecution($totalContracts, $notificationsSent);

    $this->info("Check completed. Found {$totalContracts} contracts nearing expiry.");
    if ($shouldNotify) {
      $this->info("Sent {$notificationsSent} notifications.");
    }

    // Broadcast to Pusher
    if ($totalContracts > 0) {
      broadcast(new ContractExpiryUpdateEvent($totalContracts))->toOthers();
    }
  }

  protected function processContract(Contract $contract, int $days, int &$notificationsSent): void
  {
    try {
      $users = $this->getNotificationRecipients($contract);

      foreach ($users as $user) {
        $user->notify(new ContractNotification(
          $contract,
          ContractNotification::TYPE_EXPIRY,
          ['days' => $days]
        ));
        $notificationsSent++;
      }

      Log::info('Contract expiry notification sent', [
        'contract_id' => $contract->id,
        'days_until_expiry' => $days,
        'recipients' => $users->pluck('email'),
      ]);

    } catch (\Exception $e) {
      Log::error('Failed to process contract expiry notification', [
        'contract_id' => $contract->id,
        'days_until_expiry' => $days,
        'error' => $e->getMessage(),
      ]);

      $this->error("Error processing contract #{$contract->id}: {$e->getMessage()}");
    }
  }

  // Rest of the methods remain the same...
}
