<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiryNotification;
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
        ->with(['billboards', 'client', 'users']) // Eager load relationships
        ->whereDate('end_date', $expiryDate->toDateString())
        ->where('status', 'active')
        ->whereDoesntHave('renewals') // Exclude contracts that are already being renewed
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
  }

  protected function getDaysToCheck(): array
  {
    $days = $this->option('days');
    if (empty($days)) {
      return $this->defaultDays;
    }

    return collect($days)
      ->map(fn($day) => (int) $day)
      ->filter(fn($day) => $day > 0)
      ->unique()
      ->sort()
      ->values()
      ->toArray();
  }

  protected function shouldRun(): bool
  {
    $lastRun = cache()->get('last_contract_check');
    if (!$lastRun) {
      return true;
    }

    // Only run once per day unless forced
    return Carbon::parse($lastRun)->diffInHours(now()) >= 24;
  }

  protected function processContract(Contract $contract, int $days, int &$notificationsSent): void
  {
    try {
      // Get users who should be notified
      $users = $this->getNotificationRecipients($contract);

      foreach ($users as $user) {
        if ($user->shouldReceiveNotification('contract_expiry', 'email')) {
          $user->notify(new ContractExpiryNotification($contract, $days));
          $notificationsSent++;
        }
      }

      // Log successful notifications
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

  protected function getNotificationRecipients(Contract $contract): \Illuminate\Database\Eloquent\Collection
  {
    // Get users associated with the contract
    $contractUsers = $contract->users;

    // Get users with specific roles who should be notified
    $roleUsers = User::role(['admin', 'manager'])
      ->where('is_active', true)
      ->whereNotIn('id', $contractUsers->pluck('id'))
      ->get();

    return $contractUsers->merge($roleUsers);
  }

  protected function logExecution(int $totalContracts, int $notificationsSent): void
  {
    $data = [
      'executed_at' => now(),
      'contracts_found' => $totalContracts,
      'notifications_sent' => $notificationsSent,
      'executed_by' => 'system',
    ];

    // Cache last run time
    cache()->put('last_contract_check', now(), Carbon::now()->addDay());

    // Log execution details
    Log::info('Contract expiry check completed', $data);
  }
}
