<?php

use Illuminate\Support\Facades\Schedule;

$settings = \App\Models\Settings::instance();

Schedule::command('contracts:check-expiry --notify')
  ->dailyAt('09:00')
  ->timezone($settings->timezone ?? 'Africa/Blantyre')
  ->withoutOverlapping();

// Subscription monitoring and enforcement
Schedule::command('subscriptions:monitor')
  ->hourly()
  ->timezone($settings->timezone ?? 'Africa/Blantyre')
  ->withoutOverlapping()
  ->appendOutputTo(storage_path('logs/subscription-monitoring.log'));

Schedule::command('subscriptions:enforce-limits --grace-period=24')
  ->dailyAt('02:00')
  ->timezone($settings->timezone ?? 'Africa/Blantyre')
  ->withoutOverlapping()
  ->appendOutputTo(storage_path('logs/subscription-enforcement.log'));

// More frequent monitoring during business hours
Schedule::command('subscriptions:monitor --notify-only')
  ->hourlyAt(30)
  ->between('8:00', '18:00')
  ->timezone($settings->timezone ?? 'Africa/Blantyre')
  ->weekdays()
  ->withoutOverlapping();
