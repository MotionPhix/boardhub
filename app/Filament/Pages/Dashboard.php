<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BillboardOccupancyChart;
use App\Filament\Widgets\BillboardsMap;
use App\Filament\Widgets\LatestContracts;
use App\Filament\Widgets\PopularLocations;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Location;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

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
      'location' => null,
    ];
  }

  public function getWidgets(): array
  {
    return [
      StatsOverview::class,
      BillboardsMap::class,
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
        DateRangePicker::make('dateRange')
          ->label('Date Range')
          ->displayFormat('D MMM Y')
          ->timezone('Africa/Blantyre')
          ->live()
          ->afterStateUpdated(function () {
            $this->refreshWidgets();
          })
          ->default([
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
          ]),

        Select::make('location')
          ->label('Filter by Location')
          ->multiple()
          ->options(
            Location::query()
              ->where('is_active', true)
              ->pluck('name', 'id')
          )
          ->searchable()
          ->preload()
          ->live()
          ->afterStateUpdated(function () {
            $this->refreshWidgets();
          }),
      ])
      ->columns([
        'default' => 1,
        'sm' => 2,
      ]);
  }

  protected function refreshFormData(): array
  {
    return [
      'dateRange' => [
        'from' => $this->data['dateRange']['from'] ?? now()->startOfMonth()->format('Y-m-d'),
        'to' => $this->data['dateRange']['to'] ?? now()->endOfMonth()->format('Y-m-d'),
      ],
      'location' => $this->data['location'] ?? null,
    ];
  }

  protected function refreshWidgets(): void
  {
    $this->resetPage();

    foreach ($this->getWidgets() as $widget) {
      $this->refreshWidget($widget);
    }
  }
}
