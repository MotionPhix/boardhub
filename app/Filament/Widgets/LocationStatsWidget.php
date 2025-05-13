<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use App\Models\Contract;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LocationStatsWidget extends StatsOverviewWidget
{
  protected static ?string $pollingInterval = '15s';

  protected function getStats(): array
  {
    $now = Carbon::now();
    $lastMonth = $now->copy()->subMonth();

    return [
      Stat::make('Total Locations', Location::count())
        ->description(Location::where('created_at', '>=', $lastMonth)->count() . ' new this month')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->color('success')
        ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

      Stat::make('Active Billboards', Billboard::where('is_active', true)->count())
        ->description(Billboard::where([
            ['is_active', true],
            ['created_at', '>=', $lastMonth]
          ])->count() . ' new this month')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->color('info')
        ->chart([2, 4, 6, 8, 5, 3, 7, 9]),

      Stat::make('Active Contracts', Contract::where('booking_status', 'in_use')->count())
        ->description(Contract::where([
            ['booking_status', 'in_use'],
            ['created_at', '>=', $lastMonth]
          ])->count() . ' new this month')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->color('warning')
        ->chart([3, 5, 7, 4, 6, 8, 5, 3]),
    ];
  }
}
