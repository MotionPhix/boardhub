<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Billboard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillboardOverview extends BaseWidget
{
  protected function getStats(): array
  {
    $totalRevenue = Billboard::query()
      ->withSum(['contracts' => fn ($query) => $query->where('contracts.status', 'active')], 'total_amount')
      ->get()
      ->sum('contracts_sum_total_amount');

    $occupancyRate = $this->calculateOverallOccupancyRate();

    return [
      Stat::make('Total Billboards', Billboard::count())
        ->description('Across all locations')
        ->descriptionIcon('heroicon-m-rectangle-stack')
        ->chart([
          Billboard::where('status', 'Available')->count(),
          Billboard::where('status', 'Occupied')->count(),
          Billboard::where('status', 'Maintenance')->count(),
        ])
        ->color('primary'),

      Stat::make('Overall Revenue', '$' . number_format($totalRevenue, 2))
        ->description('From active contracts')
        ->descriptionIcon('heroicon-m-currency-dollar')
        ->color('success'),

      Stat::make('Average Occupancy', number_format($occupancyRate, 1) . '%')
        ->description('Last 12 months')
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color($occupancyRate > 75 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
    ];
  }

  protected function calculateOverallOccupancyRate(): float
  {
    $billboards = Billboard::with(['contracts' => function ($query) {
      $query->where('contracts.status', 'active')
        ->where(function ($query) {
          $startDate = now()->subYear();
          $endDate = now();
          $query->whereBetween('contracts.start_date', [$startDate, $endDate])
            ->orWhereBetween('contracts.end_date', [$startDate, $endDate])
            ->orWhere(function ($query) use ($startDate, $endDate) {
              $query->where('contracts.start_date', '<=', $startDate)
                ->where('contracts.end_date', '>=', $endDate);
            });
        });
    }])->get();

    $totalBillboardDays = $billboards->count() * 365;
    $totalOccupiedDays = 0;

    foreach ($billboards as $billboard) {
      foreach ($billboard->contracts as $contract) {
        $startDate = $contract->start_date->max(now()->subYear());
        $endDate = $contract->end_date->min(now());
        $totalOccupiedDays += $startDate->diffInDays($endDate);
      }
    }

    return $totalBillboardDays > 0
      ? ($totalOccupiedDays / $totalBillboardDays) * 100
      : 0;
  }
}
