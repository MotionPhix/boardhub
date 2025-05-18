<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ContractNotification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
  protected $signature = 'notifications:test {email}';
  protected $description = 'Test the notification system';

  public function handle()
  {
    $email = $this->argument('email');
    $user = User::where('email', $email)->first();

    if (!$user) {
      $this->error("User not found with email: {$email}");
      return 1;
    }

    // Create a test contract notification
    $contract = \App\Models\Contract::first();

    if (!$contract) {
      $this->error("No contracts found in the database");
      return 1;
    }

    try {
      $user->notify(new ContractNotification(
        $contract,
        'expiry',
        ['days' => 7]
      ));

      $this->info('Test notification sent successfully!');
      $this->info('Check these places:');
      $this->line('1. Your email inbox');
      $this->line('2. The notifications bell icon in the app');
      $this->line('3. The database notifications table');

      return 0;

    } catch (\Exception $e) {
      $this->error("Error sending notification: {$e->getMessage()}");
      return 1;
    }
  }
}
