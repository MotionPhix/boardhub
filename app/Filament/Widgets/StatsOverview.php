<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use App\Models\Contract;
use App\Models\Location;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
  protected static ?string $pollingInterval = '15s';

  protected function getStats(): array
  {
    $totalRevenue = Contract::where('agreement_status', 'active')
      ->sum('total_amount');

    $activeContracts = Contract::where('agreement_status', 'active')->count();

    $occupancyRate = $this->calculateOccupancyRate();

    return [
      Stat::make('Total Revenue', 'MK ' . number_format($totalRevenue, 2))
        ->description('From active contracts')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->chart([7, 3, 4, 5, 6, 3, 5, 3])
        ->color('success'),

      Stat::make('Active Contracts', $activeContracts)
        ->description('Currently running')
        ->descriptionIcon('heroicon-m-document-check')
        ->chart([4, 3, 6, 2, 5, 3, 3, 2])
        ->color('info'),

      Stat::make('Occupancy Rate', number_format($occupancyRate, 1) . '%')
        ->description('Of total billboards')
        ->descriptionIcon('heroicon-m-presentation-chart-line')
        ->chart([3, 5, 4, 3, 6, 3, 5, 4])
        ->color('warning'),
    ];
  }

  protected function calculateOccupancyRate(): float
  {
    $totalBillboards = Billboard::count();
    if ($totalBillboards === 0) return 0;

    $occupiedBillboards = Billboard::whereHas('contracts', function ($query) {
      $query->where('agreement_status', 'active');
    })->count();

    return ($occupiedBillboards / $totalBillboards) * 100;
  }
}
