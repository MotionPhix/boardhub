<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Billboard;
use App\Models\Contract;
use App\Models\Currency;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BillboardOverview extends BaseWidget
{
  protected static ?string $pollingInterval = '15s';

  protected function getStats(): array
  {
    $defaultCurrency = Currency::getDefault();

    // Get total revenue for current month
    $currentMonthRevenue = Contract::query()
      ->whereHas('billboards')
      ->whereMonth('start_date', '<=', now())
      ->whereMonth('end_date', '>=', now())
      ->sum('contract_final_amount');

    // Get total active billboards
    $activeBillboards = Billboard::active()->count();

    // Get average billboard price
    $averagePrice = Billboard::active()
      ->where('base_price', '>', 0)
      ->avg('base_price');

    // Get total revenue trend (last 6 months)
    $revenueTrend = Contract::query()
      ->select(DB::raw('MONTH(start_date) as month'), DB::raw('SUM(contract_final_amount) as total'))
      ->whereHas('billboards')
      ->where('start_date', '>=', now()->subMonths(6))
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('total')
      ->toArray();

    // Get active billboards trend (last 6 months)
    $billboardsTrend = Billboard::query()
      ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
      ->where('created_at', '>=', now()->subMonths(6))
      ->where('is_active', true)
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('total')
      ->toArray();

    return [
      Stat::make('Current Month Revenue', function () use ($currentMonthRevenue, $defaultCurrency) {
        return $defaultCurrency?->symbol . number_format($currentMonthRevenue, 2);
      })
        ->description('Total revenue from active contracts')
        ->descriptionIcon('heroicon-m-banknotes')
        ->chart($revenueTrend)
        ->color('success'),

      Stat::make('Active Billboards', $activeBillboards)
        ->description('Currently active billboards')
        ->descriptionIcon('heroicon-m-rectangle-stack')
        ->chart($billboardsTrend)
        ->color('primary'),

      Stat::make('Average Billboard Price', function () use ($averagePrice, $defaultCurrency) {
        return $defaultCurrency?->symbol . number_format($averagePrice ?? 0, 2);
      })
        ->description('Average price per billboard')
        ->descriptionIcon('heroicon-m-calculator')
        ->color('warning'),
    ];
  }
}
