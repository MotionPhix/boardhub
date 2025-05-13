<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use App\Models\Contract;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

class BillboardStatusChart extends ChartWidget
{
  protected static ?string $heading = 'Billboard Status Distribution';

  protected static ?string $pollingInterval = '15s';

  protected static ?string $maxHeight = '300px';

  protected function getData(): array
  {
    $statuses = [
      'Available' => Billboard::where('is_active', true)
        ->whereDoesntHave('contracts', function ($query) {
          $query->where('agreement_status', Contract::STATUS_ACTIVE)
            ->where('billboard_contract.booking_status', 'in_use');
        })->count(),
      'In Use' => Billboard::where('is_active', true)
        ->whereHas('contracts', function ($query) {
          $query->where('agreement_status', Contract::STATUS_ACTIVE)
            ->where('billboard_contract.booking_status', 'in_use');
        })->count(),
      'Inactive' => Billboard::where('is_active', false)->count(),
    ];

    $colors = [
      'Available' => Color::hex('#22c55e'), // Green 500
      'In Use' => Color::hex('#3b82f6'), // Blue 500
      'Inactive' => Color::hex('#ef4444'), // Red 500
    ];

    return [
      'datasets' => [
        [
          'label' => 'Billboards',
          'data' => array_values($statuses),
          'backgroundColor' => array_map(fn ($status) => $colors[$status], array_keys($statuses)),
        ],
      ],
      'labels' => array_keys($statuses),
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }

  protected function getOptions(): array
  {
    return [
      'plugins' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];
  }

  public function getColumnSpan(): int|array
  {
    return 2;
  }
}
