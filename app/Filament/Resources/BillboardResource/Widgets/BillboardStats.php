<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Billboard;
use App\Models\Currency;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillboardStats extends BaseWidget
{
  public ?Billboard $record = null;

  protected int|string|array $columnSpan = 'full';

  protected static ?string $pollingInterval = '15s';

  protected function getStats(): array
  {
    if (!$this->record) {
      return [];
    }

    $defaultCurrency = Currency::getDefault();
    $now = Carbon::now();
    $yearAgo = $now->copy()->subYear();
    $billboardId = $this->record->id;

    // Calculate total revenue for this billboard
    $totalRevenue = $this->record->contracts()
      ->wherePivot('billboard_id', $billboardId)
      ->whereDate('contracts.start_date', '>=', $yearAgo)
      ->sum(DB::raw('COALESCE(billboard_contract.billboard_final_price, 0)'));

    // Get the revenue chart data for the last 12 months
    $revenueChart = $this->getMonthlyData(
      'SUM(COALESCE(billboard_contract.billboard_final_price, 0)) as revenue',
      'revenue'
    );

    // Get active contracts count
    $activeContracts = $this->record->contracts()
      ->wherePivot('billboard_id', $billboardId)
      ->whereDate('start_date', '<=', $now)
      ->whereDate('end_date', '>=', $now)
      ->count();

    // Get contracts trend for the last 12 months
    $contractsTrend = $this->getMonthlyData(
      'COUNT(*) as total',
      'total'
    );

    // Calculate average contract value
    $averageValue = $this->record->contracts()
      ->wherePivot('billboard_id', $billboardId)
      ->whereDate('contracts.start_date', '>=', $yearAgo)
      ->avg(DB::raw('COALESCE(billboard_contract.billboard_final_price, 0)')) ?? 0;

    // Get average value trend
    $averageValueTrend = $this->getMonthlyData(
      'AVG(COALESCE(billboard_contract.billboard_final_price, 0)) as avg_value',
      'avg_value'
    );

    return [
      Stat::make('Total Revenue', fn () =>
        $defaultCurrency?->symbol . number_format($totalRevenue, 2)
      )
        ->description('Last 12 months')
        ->descriptionIcon('heroicon-m-banknotes')
        ->chart($revenueChart)
        ->color('success'),

      Stat::make('Active Contracts', $activeContracts)
        ->description('Currently active')
        ->descriptionIcon('heroicon-m-document-text')
        ->chart($contractsTrend)
        ->color('primary'),

      Stat::make('Average Contract Value', fn () =>
        $defaultCurrency?->symbol . number_format($averageValue, 2)
      )
        ->description('Per contract (last 12 months)')
        ->descriptionIcon('heroicon-m-calculator')
        ->chart($averageValueTrend)
        ->color('warning'),
    ];
  }

  protected function getMonthlyData(string $selectRaw, string $pluckKey): array
  {
    $now = Carbon::now();
    $yearAgo = $now->copy()->subYear();

    return $this->record->contracts()
      ->wherePivot('billboard_id', $this->record->id)
      ->whereDate('contracts.start_date', '>=', $yearAgo)
      ->select(
        DB::raw('DATE_FORMAT(contracts.start_date, "%Y-%m") as month'),
        DB::raw($selectRaw)
      )
      ->groupBy('month')
      ->orderBy('month')
      ->pluck($pluckKey)
      ->toArray();
  }

  public static function canView(): bool
  {
    return true;
  }
}
