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
      ->select(DB::raw('SUM(billboard_contract.billboard_final_price) as total_revenue'))
      ->join('billboard_contract', function ($join) {
        $join->on('contracts.id', '=', 'billboard_contract.contract_id')
          ->where('billboard_contract.billboard_id', '=', $this->record->id);
      })
      ->where('contracts.start_date', '>=', now()->subYear())
      ->first()
      ->total_revenue ?? 0;

    // Get the revenue chart data
    $revenueChart = $this->record->contracts()
      ->select(DB::raw('
                DATE_FORMAT(contracts.start_date, "%Y-%m") as month,
                SUM(billboard_contract.billboard_final_price) as revenue
            '))
      ->join('billboard_contract', function ($join) {
        $join->on('contracts.id', '=', 'billboard_contract.contract_id')
          ->where('billboard_contract.billboard_id', '=', $this->record->id);
      })
      ->where('contracts.start_date', '>=', now()->subMonths(12))
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
      ->select(DB::raw('DATE_FORMAT(start_date, "%Y-%m") as month'), DB::raw('COUNT(*) as total'))
      ->where('start_date', '>=', now()->subMonths(12))
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('total')
      ->toArray();

    // Calculate average contract value
    $averageValue = $this->record->contracts()
      ->select(DB::raw('AVG(billboard_contract.billboard_final_price) as avg_value'))
      ->join('billboard_contract', function ($join) {
        $join->on('contracts.id', '=', 'billboard_contract.contract_id')
          ->where('billboard_contract.billboard_id', '=', $this->record->id);
      })
      ->where('contracts.start_date', '>=', now()->subYear())
      ->first()
      ->avg_value ?? 0;

    // Get average value trend
    $averageValueTrend = $this->record->contracts()
      ->select(DB::raw('
                DATE_FORMAT(contracts.start_date, "%Y-%m") as month,
                AVG(billboard_contract.billboard_final_price) as avg_value
            '))
      ->join('billboard_contract', function ($join) {
        $join->on('contracts.id', '=', 'billboard_contract.contract_id')
          ->where('billboard_contract.billboard_id', '=', $this->record->id);
      })
      ->where('contracts.start_date', '>=', now()->subMonths(12))
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
