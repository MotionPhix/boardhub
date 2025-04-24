<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class LogSuccessfulLogin
{
  public function handle(Login $event): void
  {
    $user = $event->user;
    $ip = request()->ip();

    // Get device and browser info
    $agent = new Agent();

    // Get location info
    $location = Location::get($ip);

    $user->loginActivities()->create([
      'ip_address' => $ip,
      'user_agent' => request()->userAgent(),
      'login_at' => now(),
      'login_successful' => true,
      'location' => $location ? "{$location->city}, {$location->countryName}" : null,
      'device' => $agent->device() ? $agent->device() : ($agent->isDesktop() ? 'Desktop' : 'Unknown'),
      'browser' => $agent->browser(),
    ]);

    // Update last login timestamp
    $user->update(['last_login_at' => now()]);
  }
}
