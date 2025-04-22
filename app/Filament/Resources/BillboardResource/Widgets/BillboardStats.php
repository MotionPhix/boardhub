<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BillboardStats extends BaseWidget
{
  public $record;

  protected function getStats(): array
  {
    $activeContract = $this->record->contracts()
      ->where('contracts.status', 'active')
      ->where('contracts.start_date', '<=', now())
      ->where('contracts.end_date', '>=', now())
      ->first();

    $totalRevenue = $this->record->contracts()
      ->where('contracts.status', 'active')
      ->sum('total_amount');

    $occupancyRate = $this->calculateOccupancyRate();

    return [
      Stat::make('Current Status', $this->record->status)
        ->description($activeContract
          ? "Contracted until " . $activeContract->end_date->format('M d, Y')
          : ($this->record->available_until
            ? "Available until " . $this->record->available_until->format('M d, Y')
            : "No end date set"
          ))
        ->color(match ($this->record->status) {
          'Available' => 'success',
          'Occupied' => 'warning',
          'Maintenance' => 'danger',
          default => 'gray',
        }),

      Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
        ->description('From all contracts')
        ->color('success')
        ->chart($this->getRevenueChart()),

      Stat::make('Occupancy Rate', number_format($occupancyRate, 1) . '%')
        ->description('Last 12 months')
        ->color($occupancyRate > 75 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
    ];
  }

  protected function calculateOccupancyRate(): float
  {
    $startDate = now()->subYear();
    $endDate = now();
    $totalDays = $startDate->diffInDays($endDate);

    $occupiedDays = $this->record->contracts()
      ->where('contracts.status', 'active')
      ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('contracts.start_date', [$startDate, $endDate])
          ->orWhereBetween('contracts.end_date', [$startDate, $endDate])
          ->orWhere(function ($query) use ($startDate, $endDate) {
            $query->where('contracts.start_date', '<=', $startDate)
              ->where('contracts.end_date', '>=', $endDate);
          });
      })
      ->get()
      ->reduce(function ($carry, $contract) use ($startDate, $endDate) {
        $contractStart = Carbon::parse($contract->start_date)->max($startDate);
        $contractEnd = Carbon::parse($contract->end_date)->min($endDate);
        return $carry + $contractStart->diffInDays($contractEnd);
      }, 0);

    return ($occupiedDays / $totalDays) * 100;
  }

  protected function getRevenueChart(): array
  {
    $revenues = $this->record->contracts()
      ->where('contracts.status', 'active')
      ->where('contracts.start_date', '>=', now()->subMonths(12))
      ->get()
      ->groupBy(fn ($contract) => $contract->start_date->format('M'))
      ->map(fn ($contracts) => $contracts->sum('total_amount'))
      ->values()
      ->toArray();

    // Pad array to 12 elements if less than 12 months of data
    while (count($revenues) < 12) {
      array_unshift($revenues, 0);
    }

    return array_slice($revenues, -12);
  }
}
