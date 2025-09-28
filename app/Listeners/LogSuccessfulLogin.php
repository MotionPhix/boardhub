<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
  public function handle(Login $event): void
  {
    $user = $event->user;

    // For now, just log that user logged in successfully
    // Future enhancements can add login tracking table
    logger()->info('User logged in successfully', [
      'user_id' => $user->id,
      'email' => $user->email,
      'ip' => request()->ip(),
      'user_agent' => request()->userAgent(),
    ]);
  }
}
