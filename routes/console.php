<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Avoid calling Settings::instance() at include time because that performs a DB query
// which will fail in test environments or before migrations run. Instead, check if the
// `settings` table exists and load settings lazily.

$timezone = 'Africa/Blantyre';

if (Schema::hasTable('settings')) {
    $settings = \App\Models\Settings::instance();
    $timezone = $settings->timezone ?? $timezone;
}

Schedule::command('contracts:check-expiry --notify')
  ->dailyAt('09:00')
  ->timezone($timezone)
  ->withoutOverlapping();

// Subscription monitoring and enforcement
Schedule::command('subscriptions:monitor')
  ->hourly()
  ->timezone($timezone)
  ->withoutOverlapping()
  ->appendOutputTo(storage_path('logs/subscription-monitoring.log'));

Schedule::command('subscriptions:enforce-limits --grace-period=24')
  ->dailyAt('02:00')
  ->timezone($timezone)
  ->withoutOverlapping()
  ->appendOutputTo(storage_path('logs/subscription-enforcement.log'));

// More frequent monitoring during business hours
Schedule::command('subscriptions:monitor --notify-only')
  ->hourlyAt(30)
  ->between('8:00', '18:00')
  ->timezone($timezone)
  ->weekdays()
  ->withoutOverlapping();
