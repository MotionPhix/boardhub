<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use Filament\Widgets\ChartWidget;

class BillboardOccupancyChart extends ChartWidget
{
  protected static ?string $heading = 'Billboard Occupancy';

  protected static ?int $sort = 5;

  protected function getData(): array
  {
    $total = Billboard::count();
    $occupied = Billboard::whereHas('contracts', function ($query) {
      $query->where('agreement_status', 'active');
    })->count();
    $available = $total - $occupied;

    return [
      'datasets' => [
        [
          'data' => [$occupied, $available],
          'backgroundColor' => ['#0891b2', '#64748b'],
        ],
      ],
      'labels' => ['Occupied', 'Available'],
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }
}
