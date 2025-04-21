<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsOverview extends BaseWidget
{
  protected function getStats(): array
  {
    return [
      Stat::make('Total Clients', Client::count())
        ->description('Total number of registered clients')
        ->descriptionIcon('heroicon-m-users')
        ->color('primary'),

      Stat::make('Active Contracts', Contract::where('status', 'active')
        ->where('end_date', '>=', now())
        ->count())
        ->description('Currently active contracts')
        ->descriptionIcon('heroicon-m-document-check')
        ->color('success'),

      Stat::make('Total Contract Value', number_format(Contract::where('status', 'active')
        ->where('end_date', '>=', now())
        ->sum('total_amount'), 2))
        ->description('Value of active contracts')
        ->descriptionIcon('heroicon-m-currency-dollar')
        ->color('success'),
    ];
  }
}
