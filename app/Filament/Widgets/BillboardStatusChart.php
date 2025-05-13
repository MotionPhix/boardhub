<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
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
          $query->where('booking_status', 'in_use');
        })->count(),
      'In Use' => Billboard::where('is_active', true)
        ->whereHas('contracts', function ($query) {
          $query->where('booking_status', 'in_use');
        })->count(),
      'Inactive' => Billboard::where('is_active', false)->count(),
    ];

    return [
      'datasets' => [
        [
          'label' => 'Billboards',
          'data' => array_values($statuses),
          'backgroundColor' => [
            Color::Green->tone(500),
            Color::Blue->tone(500),
            Color::Red->tone(500),
          ],
        ],
      ],
      'labels' => array_keys($statuses),
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }
}
