<?php

namespace App\Filament\Resources\BillboardResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillboardStats extends BaseWidget
{
  public $record;

  protected function getStats(): array
  {
    $currentContract = $this->record->contracts()
      ->whereDate('start_date', '<=', now())
      ->whereDate('end_date', '>=', now())
      ->first();

    $totalRevenue = $this->record->contracts()
      ->sum('total_amount');

    $contractsCount = $this->record->contracts()->count();

    return [
      Stat::make('Physical Status', $this->record::getPhysicalStatuses()[$this->record->physical_status])
        ->description($currentContract
          ? "Currently occupied until " . $currentContract->end_date->format('M d, Y')
          : "Currently available")
        ->color(match ($this->record->physical_status) {
          $this->record::PHYSICAL_STATUS_OPERATIONAL => 'success',
          $this->record::PHYSICAL_STATUS_MAINTENANCE => 'warning',
          $this->record::PHYSICAL_STATUS_DAMAGED => 'danger',
        }),

      Stat::make('Total Revenue', 'MK ' . number_format($totalRevenue, 2))
        ->description($contractsCount . ' total contracts')
        ->color('success'),

      Stat::make('Current Rate', 'MK ' . number_format($this->record->price, 2))
        ->description('Per booking period')
        ->color('info'),
    ];
  }
}
