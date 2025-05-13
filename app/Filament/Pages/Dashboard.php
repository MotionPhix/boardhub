<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BillboardStatusChart;
use App\Filament\Widgets\ContractExpiryWidget;
use App\Filament\Widgets\LocationStatsWidget;
use App\Filament\Widgets\PopularLocationsWidget;
use App\Filament\Widgets\RecentLocationsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
  protected static ?string $navigationIcon = 'heroicon-o-home';

  protected static ?string $navigationLabel = 'Dashboard';

  protected static ?string $title = 'Dashboard';

  protected static ?int $navigationSort = -2;

  public function getWidgets(): array
  {
    return [
      LocationStatsWidget::class,
      BillboardStatusChart::class,
      RecentLocationsWidget::class,
      PopularLocationsWidget::class,
      ContractExpiryWidget::class,
    ];
  }

  public function getColumns(): int|array
  {
    return 2;
  }
}
