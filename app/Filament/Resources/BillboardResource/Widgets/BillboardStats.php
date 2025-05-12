<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use App\Models\Settings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class BillboardStats extends BaseWidget
{
  public $record;

  protected function getStats(): array
  {
    $currentContract = $this->record->current_contract;
    $currency = $this->record->currency_code;
    $currencySymbol = Settings::getAvailableCurrencies()[$currency]['symbol'] ?? 'MK';

    $stats = [
      // Status stat with availability and contract info
      Stat::make('Physical Status', $this->record::getPhysicalStatuses()[$this->record->physical_status])
        ->description($currentContract
          ? "Occupied until " . $currentContract->end_date->format('M d, Y')
          : "Currently available")
        ->color(match ($this->record->physical_status) {
          $this->record::PHYSICAL_STATUS_OPERATIONAL => 'success',
          $this->record::PHYSICAL_STATUS_MAINTENANCE => 'warning',
          $this->record::PHYSICAL_STATUS_DAMAGED => 'danger',
        })
        ->icon(match ($this->record->physical_status) {
          $this->record::PHYSICAL_STATUS_OPERATIONAL => 'heroicon-o-check-circle',
          $this->record::PHYSICAL_STATUS_MAINTENANCE => 'heroicon-o-wrench',
          $this->record::PHYSICAL_STATUS_DAMAGED => 'heroicon-o-exclamation-triangle',
        }),
    ];

    // Only show financial stats for authorized roles
    if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      // Calculate revenue stats
      $totalRevenue = $this->record->contracts()->sum('total_amount');
      $contractsCount = $this->record->contracts()->count();
      $yearlyRevenue = $this->record->contracts()
        ->whereYear('start_date', Carbon::now()->year)
        ->sum('total_amount');

      // Current pricing stats
      $stats[] = Stat::make('Current Rates', $currencySymbol . ' ' . number_format($this->record->base_price, 2))
        ->description('Base price per month')
        ->chart($this->getPriceHistory())
        ->color('info')
        ->icon('heroicon-o-currency-dollar');

      // Revenue stats
      $stats[] = Stat::make('Total Revenue', $currencySymbol . ' ' . number_format($totalRevenue, 2))
        ->description(sprintf(
          '%d contracts (%s this year)',
          $contractsCount,
          $currencySymbol . ' ' . number_format($yearlyRevenue, 2)
        ))
        ->color('success')
        ->icon('heroicon-o-banknotes');

      // Occupancy rate
      if ($contractsCount > 0) {
        $occupancyRate = $this->calculateOccupancyRate();
        $stats[] = Stat::make('Occupancy Rate', number_format($occupancyRate, 1) . '%')
          ->description($this->getOccupancyTrend())
          ->chart($this->getOccupancyHistory())
          ->color($this->getOccupancyColor($occupancyRate))
          ->icon('heroicon-o-chart-bar');
      }
    }

    return $stats;
  }

  protected function getPriceHistory(): array
  {
    // Get price changes over the last 12 months from contracts
    return $this->record->contracts()
      ->orderBy('start_date')
      ->where('start_date', '>=', now()->subMonths(12))
      ->pluck('base_price')
      ->map(fn ($price) => (float) $price)
      ->toArray();
  }

  protected function calculateOccupancyRate(): float
  {
    $startDate = now()->subYear();
    $endDate = now();
    $totalDays = $startDate->diffInDays($endDate);

    $occupiedDays = $this->record->contracts()
      ->where('start_date', '<=', $endDate)
      ->where('end_date', '>=', $startDate)
      ->get()
      ->sum(function ($contract) use ($startDate, $endDate) {
        $contractStart = max($contract->start_date, $startDate);
        $contractEnd = min($contract->end_date, $endDate);
        return $contractStart->diffInDays($contractEnd);
      });

    return ($occupiedDays / $totalDays) * 100;
  }

  protected function getOccupancyHistory(): array
  {
    // Calculate monthly occupancy rates for the last 12 months
    $occupancyRates = collect(range(11, 0))->map(function ($monthsAgo) {
      $month = now()->subMonths($monthsAgo);
      $daysInMonth = $month->daysInMonth;

      $occupiedDays = $this->record->contracts()
        ->where('start_date', '<=', $month->endOfMonth())
        ->where('end_date', '>=', $month->startOfMonth())
        ->get()
        ->sum(function ($contract) use ($month) {
          $contractStart = max($contract->start_date, $month->startOfMonth());
          $contractEnd = min($contract->end_date, $month->endOfMonth());
          return $contractStart->diffInDays($contractEnd);
        });

      return ($occupiedDays / $daysInMonth) * 100;
    });

    return $occupancyRates->toArray();
  }

  protected function getOccupancyTrend(): string
  {
    $recentMonths = collect($this->getOccupancyHistory())->take(-3);
    $trend = $recentMonths->last() - $recentMonths->first();

    if (abs($trend) < 5) {
      return 'Stable occupancy rate';
    }

    return $trend > 0
      ? 'Increasing trend'
      : 'Decreasing trend';
  }

  protected function getOccupancyColor(float $rate): string
  {
    return match (true) {
      $rate >= 80 => 'success',
      $rate >= 60 => 'info',
      $rate >= 40 => 'warning',
      default => 'danger',
    };
  }
}
