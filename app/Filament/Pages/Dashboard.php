<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BillboardOccupancyChart;
use App\Filament\Widgets\BillboardsMap;
use App\Filament\Widgets\LatestContracts;
use App\Filament\Widgets\PopularLocations;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;

class Dashboard extends BaseDashboard
{
  use HasFiltersForm;

  public ?array $data = [];

  public function mount(): void
  {
    $this->data = [
      'dateRange' => [
        'from' => now()->startOfMonth()->format('Y-m-d'),
        'to' => now()->endOfMonth()->format('Y-m-d'),
      ],
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      StatsOverview::class,
      BillboardsMap::class,
    ];
  }

  protected function getContentWidgets(): array
  {
    return [
      LatestContracts::class,
      RevenueChart::class,
      BillboardOccupancyChart::class,
      PopularLocations::class,
    ];
  }

  public function filtersForm(Form $form): Form
  {
    return $form
      ->schema([
        DatePicker::make('dateRange')
          ->label('Date Range')
          ->range()
          ->default([
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
          ]),

        Select::make('location')
          ->label('Filter by Location')
          ->relationship('locations', 'name')
          ->searchable()
          ->preload()
          ->multiple(),
      ])
      ->columns(2);
  }
}
