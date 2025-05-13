<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Billboard;
use App\Models\Settings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BillboardStats extends BaseWidget
{
  public ?Billboard $record = null;

  protected function getStats(): array
  {
    $currency = Settings::getDefaultCurrency();

    // Calculate total revenue for this billboard
    $totalRevenue = $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->where('contracts.start_date', '>=', now()->subYear())
      ->sum('billboard_contract.billboard_final_price');

    // Get the revenue chart data
    $revenueChart = $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->where('contracts.start_date', '>=', now()->subMonths(12))
      ->selectRaw('DATE_FORMAT(contracts.start_date, "%Y-%m") as month')
      ->selectRaw('SUM(billboard_contract.billboard_final_price) as revenue')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('revenue')
      ->toArray();

    // Get active contracts count
    $activeContracts = $this->record->contracts()
      ->wherePivot('booking_status', 'in_use')
      ->count();

    // Get active contracts trend
    $contractsTrend = $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->where('start_date', '>=', now()->subMonths(12))
      ->selectRaw('DATE_FORMAT(start_date, "%Y-%m") as month')
      ->selectRaw('COUNT(*) as total')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('total')
      ->toArray();

    // Calculate average contract value
    $averageValue = $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->where('contracts.start_date', '>=', now()->subYear())
      ->avg('billboard_contract.billboard_final_price') ?? 0;

    // Get average value trend
    $averageValueTrend = $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->where('contracts.start_date', '>=', now()->subMonths(12))
      ->selectRaw('DATE_FORMAT(contracts.start_date, "%Y-%m") as month')
      ->selectRaw('AVG(billboard_contract.billboard_final_price) as avg_value')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('avg_value')
      ->toArray();

    return [
      Stat::make('Total Revenue', function () use ($totalRevenue, $currency) {
        return $currency['symbol'] . number_format($totalRevenue, 2);
      })
        ->description('Last 12 months')
        ->descriptionIcon('heroicon-m-banknotes')
        ->chart($revenueChart)
        ->color('success'),

      Stat::make('Active Contracts', $activeContracts)
        ->description('Currently active')
        ->descriptionIcon('heroicon-m-document-text')
        ->chart($contractsTrend)
        ->color('primary'),

      Stat::make('Average Contract Value', function () use ($averageValue, $currency) {
        return $currency['symbol'] . number_format($averageValue, 2);
      })
        ->description('Per contract value')
        ->descriptionIcon('heroicon-m-calculator')
        ->chart($averageValueTrend)
        ->color('warning'),
    ];
  }
}
