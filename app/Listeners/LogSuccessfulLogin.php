<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\UserLoginActivity;
use Illuminate\Support\Facades\Http;

class LogSuccessfulLogin
{
  public function handle(Login $event): void
  {
    $user = $event->user;
    $request = request();

    // Get location from IP
    $ipData = Http::get("http://ip-api.com/json/{$request->ip()}")->json();

    $activity = new UserLoginActivity([
      'user_id' => $user->id,
      'ip_address' => $request->ip(),
      'user_agent' => $request->userAgent(),
      'location' => $ipData['status'] === 'success'
        ? "{$ipData['city']}, {$ipData['country']}"
        : null,
      'login_at' => now(),
      'login_successful' => true,
      'login_type' => 'form',
      'details' => [
        'browser' => get_browser_name($request->userAgent()),
        'platform' => get_platform($request->userAgent()),
      ],
    ]);

    $activity->save();

    // Update user's last login timestamp
    $user->update(['last_login_at' => now()]);
  }
}
