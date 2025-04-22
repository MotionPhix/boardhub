<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Billboard;
use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BillboardOverview extends BaseWidget
{
  protected function getStats(): array
  {
    $billboardStats = $this->getBillboardStats();
    $revenueStats = $this->getRevenueStats();
    $occupancyRate = $this->calculateOccupancyRate();

    return [
      Stat::make('Total Billboards', $billboardStats['total'])
        ->description('By Physical Status')
        ->descriptionIcon('heroicon-m-rectangle-stack')
        ->chart([
          $billboardStats['operational'],
          $billboardStats['maintenance'],
          $billboardStats['damaged'],
        ])
        ->color('primary'),

      Stat::make('Current Revenue', 'MK ' . number_format($revenueStats['current_month'], 2))
        ->description($revenueStats['trend'] . '% from last month')
        ->descriptionIcon($revenueStats['trend'] > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
        ->color($revenueStats['trend'] > 0 ? 'success' : 'danger'),

      Stat::make('Occupancy Rate', number_format($occupancyRate, 1) . '%')
        ->description('Of operational billboards')
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color($this->getOccupancyRateColor($occupancyRate)),
    ];
  }

  protected function getBillboardStats(): array
  {
    $stats = Billboard::query()
      ->selectRaw('COUNT(*) as total')
      ->selectRaw("COUNT(CASE WHEN physical_status = ? THEN 1 END) as operational", [Billboard::PHYSICAL_STATUS_OPERATIONAL])
      ->selectRaw("COUNT(CASE WHEN physical_status = ? THEN 1 END) as maintenance", [Billboard::PHYSICAL_STATUS_MAINTENANCE])
      ->selectRaw("COUNT(CASE WHEN physical_status = ? THEN 1 END) as damaged", [Billboard::PHYSICAL_STATUS_DAMAGED])
      ->first();

    return [
      'total' => $stats->total,
      'operational' => $stats->operational,
      'maintenance' => $stats->maintenance,
      'damaged' => $stats->damaged,
    ];
  }

  protected function getRevenueStats(): array
  {
    $currentMonth = now()->startOfMonth();
    $lastMonth = now()->subMonth()->startOfMonth();

    $currentMonthRevenue = Contract::whereHas('billboards', function ($query) use ($currentMonth) {
      $query->whereDate('start_date', '<=', now())
        ->whereDate('end_date', '>=', now());
    })->sum('total_amount');

    $lastMonthRevenue = Contract::whereHas('billboards', function ($query) use ($lastMonth) {
      $query->whereDate('start_date', '<=', $lastMonth->endOfMonth())
        ->whereDate('end_date', '>=', $lastMonth);
    })->sum('total_amount');

    $trend = $lastMonthRevenue > 0
      ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
      : 100;

    return [
      'current_month' => $currentMonthRevenue,
      'last_month' => $lastMonthRevenue,
      'trend' => round($trend, 1),
    ];
  }

  protected function calculateOccupancyRate(): float
  {
    $operationalBillboards = Billboard::where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)->count();

    if ($operationalBillboards === 0) {
      return 0;
    }

    $occupiedBillboards = Billboard::where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)
      ->whereHas('contracts', function ($query) {
        $query->whereDate('start_date', '<=', now())
          ->whereDate('end_date', '>=', now());
      })
      ->count();

    return ($occupiedBillboards / $operationalBillboards) * 100;
  }

  protected function getOccupancyRateColor(float $rate): string
  {
    return match(true) {
      $rate >= 80 => 'success',
      $rate >= 50 => 'warning',
      default => 'danger',
    };
  }
}
