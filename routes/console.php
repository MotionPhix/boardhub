<?php

use Illuminate\Support\Facades\Schedule;

$settings = \App\Models\Settings::instance();

Schedule::command('contracts:check-expiry --notify')
  ->dailyAt('09:00')
  ->timezone($settings->timezone ?? 'Africa/Blantyre')
  ->withoutOverlapping();
