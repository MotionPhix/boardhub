<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Filament\Widgets\ChartWidget;

class PopularLocations extends ChartWidget
{
  protected static ?string $heading = 'Most Popular Locations';

  protected static ?int $sort = 6;

  protected function getData(): array
  {
    $locations = Location::withCount(['billboards' => function ($query) {
      $query->whereHas('contracts', function ($q) {
        $q->where('agreement_status', 'active')
          ->whereHas('billboards', function($bq) {
            $bq->where('booking_status', 'active');
          });
      });
    }])
      ->orderByDesc('billboards_count')
      ->limit(5)
      ->get();

    return [
      'datasets' => [
        [
          'data' => $locations->pluck('billboards_count')->toArray(),
          'backgroundColor' => [
            '#0891b2',
            '#0284c7',
            '#2563eb',
            '#4f46e5',
            '#7c3aed',
          ],
        ],
      ],
      'labels' => $locations->pluck('name')->toArray(),
    ];
  }

  protected function getType(): string
  {
    return 'bar';
  }
}
