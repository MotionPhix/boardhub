<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

\Illuminate\Support\Facades\Schedule::command('contracts:check-expiring')
  ->dailyAt('09:00')
  ->timezone('Africa/Blantyre');
