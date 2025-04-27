<?php

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiryNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Contract expiry check command
Artisan::command('contracts:check-expiry
    {--days=* : Days before expiry to check (default: 30,14,7,3,1)}
    {--notify : Send notifications to users}
    {--force : Force check regardless of last run time}', function () {

  $defaultDays = [30, 14, 7, 3, 1];
  $shouldNotify = $this->option('notify');
  $force = $this->option('force');

  // Get days to check
  $daysToCheck = $this->option('days');
  if (empty($daysToCheck)) {
    $daysToCheck = $defaultDays;
  } else {
    $daysToCheck = collect($daysToCheck)
      ->map(fn($day) => (int) $day)
      ->filter(fn($day) => $day > 0)
      ->unique()
      ->sort()
      ->values()
      ->toArray();
  }

  // Check if we should run based on last execution time
  if (!$force && cache()->has('last_contract_check')) {
    $lastRun = cache()->get('last_contract_check');
    if (Carbon::parse($lastRun)->diffInHours(now()) < 24) {
      $this->info('Command was recently run. Use --force to override.');
      return;
    }
  }

  $now = Carbon::now();
  $totalContracts = 0;
  $notificationsSent = 0;

  foreach ($daysToCheck as $days) {
    $expiryDate = $now->copy()->addDays($days)->startOfDay();

    $contracts = Contract::query()
      ->with(['billboards', 'client', 'users'])
      ->whereDate('end_date', $expiryDate->toDateString())
      ->where('agreement_status', Contract::AGREEMENT_STATUS_ACTIVE) // Fixed: changed 'status' to 'agreement_status'
      ->whereDoesntHave('renewals')
      ->get();

    $count = $contracts->count();
    $totalContracts += $count;

    $this->info("Found {$count} contracts expiring in {$days} days.");

    if ($count > 0 && $shouldNotify) {
      foreach ($contracts as $contract) {
        try {
          // Get notification recipients
          $contractUsers = $contract->users;
          $roleUsers = User::role(['admin', 'manager'])
            ->where('is_active', true)
            ->whereNotIn('id', $contractUsers->pluck('id'))
            ->get();

          $users = $contractUsers->merge($roleUsers);

          foreach ($users as $user) {
            if ($user->shouldReceiveNotification('contract_expiry', 'email')) {
              $user->notify(new ContractExpiryNotification($contract, $days));
              $notificationsSent++;

              // Record that notification was sent
              $contract->recordNotificationSent();
            }
          }

          Log::info('Contract expiry notification sent', [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'days_until_expiry' => $days,
            'recipients' => $users->pluck('email'),
          ]);

        } catch (\Exception $e) {
          Log::error('Failed to process contract expiry notification', [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'days_until_expiry' => $days,
            'error' => $e->getMessage(),
          ]);

          $this->error("Error processing contract #{$contract->contract_number}: {$e->getMessage()}");
        }
      }
    }
  }

  // Log execution
  $data = [
    'executed_at' => now(),
    'contracts_found' => $totalContracts,
    'notifications_sent' => $notificationsSent,
    'executed_by' => 'MotionPhix',
  ];

  cache()->put('last_contract_check', now(), Carbon::now()->addDay());
  Log::info('Contract expiry check completed', $data);

  $this->info("Check completed. Found {$totalContracts} contracts nearing expiry.");
  if ($shouldNotify) {
    $this->info("Sent {$notificationsSent} notifications.");
  }
})->purpose('Check for contracts nearing expiry and send notifications');

// Schedule the commands
Schedule::command('contracts:check-expiry --notify')
  ->dailyAt('09:00')
  ->timezone('Africa/Blantyre')
  ->withoutOverlapping()
  ->emailOutputOnFailure(config('mail.admin_email'))
  ->then(function () {
    Log::info('Contract expiry check scheduled task completed');
  })
  ->onFailure(function () {
    Log::error('Contract expiry check scheduled task failed');
  });

// Weekend follow-ups
Schedule::command('contracts:check-expiry --days=2,1 --notify')
  ->fridays()
  ->at('14:00')
  ->timezone('Africa/Blantyre')
  ->withoutOverlapping();

// System maintenance tasks
Schedule::command('cache:prune-stale-tags')->daily();
Schedule::command('auth:clear-resets')->daily();
Schedule::command('queue:prune-failed')->daily();
Schedule::command('db:backup')->dailyAt('01:00');
Schedule::command('cache:clear')->weekly();

// Generate monthly contract report
Artisan::command('contracts:report {--monthly}', function() {
  $this->info('Generating contract report...');

  $query = Contract::query()
    ->with(['billboards', 'client'])
    ->where('status', 'active');

  if ($this->option('monthly')) {
    $query->whereMonth('created_at', now()->month);
  }

  $contracts = $query->get();

  // Process report data
  $reportData = [
    'total_contracts' => $contracts->count(),
    'total_value' => $contracts->sum('value'),
    'expiring_soon' => $contracts->where('end_date', '<=', now()->addDays(30))->count(),
    'new_this_month' => $contracts->where('created_at', '>=', now()->startOfMonth())->count(),
  ];

  // Log report data
  Log::info('Contract report generated', $reportData);

  $this->table(
    ['Metric', 'Value'],
    collect($reportData)->map(fn ($value, $key) => [
      str($key)->title()->replace('_', ' '),
      $value
    ])
  );
})->purpose('Generate a contract status report');

// Schedule monthly report
Schedule::command('contracts:report --monthly')
  ->monthlyOn(1, '07:00')
  ->timezone('Africa/Blantyre');
