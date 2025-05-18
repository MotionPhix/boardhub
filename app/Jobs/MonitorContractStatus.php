<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Events\ContractExpiringEvent;
use App\Notifications\ContractExpiringNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorContractStatus implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle(): void
  {
    $warningDays = [30, 14, 7, 3, 1]; // Days before expiration to send warnings

    Contract::query()
      ->where('agreement_status', 'active')
      ->where('end_date', '>', now())
      ->each(function (Contract $contract) use ($warningDays) {
        $daysUntilExpiration = Carbon::now()->diffInDays($contract->end_date);

        if (in_array($daysUntilExpiration, $warningDays)) {
          // Send notification to users
          $users = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager']);
          })->get();

          foreach ($users as $user) {
            $user->notify(new ContractExpiringNotification($contract, $daysUntilExpiration));
          }

          // Broadcast event for real-time notification
          broadcast(new ContractExpiringEvent($contract, $daysUntilExpiration))->toOthers();
        }

        // Auto-update status if contract has expired
        if (Carbon::now()->isAfter($contract->end_date)) {
          $contract->update(['agreement_status' => 'completed']);
        }
      });
  }
}
