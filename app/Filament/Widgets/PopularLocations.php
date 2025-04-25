<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PopularLocations extends ApexChartWidget
{
  /**
   * Chart Id
   *
   * @var string|null
   */
  protected static ?string $chartId = 'popularLocations';

  /**
   * Widget Title
   *
   * @var string|null
   */
  protected static ?string $heading = 'Most Popular Locations';

  protected static ?int $sort = 6;

  protected int | string | array $columnSpan = 'full';

  /**
   * Chart options (series, labels, types, size, animations...)
   * https://apexcharts.com/docs/options
   *
   * @return array
   */
  protected function getOptions(): array
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
      'chart' => [
        'type' => 'bar',
        'height' => 300,
        'toolbar' => [
          'show' => true,
        ],
      ],
      'series' => [
        [
          'name' => 'Active Billboards',
          'data' => $locations->pluck('billboards_count')->toArray(),
        ],
      ],
      'xaxis' => [
        'categories' => $locations->pluck('name')->toArray(),
        'labels' => [
          'style' => [
            'fontFamily' => 'inherit',
            'fontWeight' => 600,
          ],
        ],
      ],
      'colors' => [
        '#0891b2',
        '#0284c7',
        '#2563eb',
        '#4f46e5',
        '#7c3aed',
      ],
      'plotOptions' => [
        'bar' => [
          'borderRadius' => 3,
          'horizontal' => true,
        ],
      ],
      'dataLabels' => [
        'enabled' => true,
        'formatter' => 'function (val) { return val + " billboards" }',
      ],
      'tooltip' => [
        'enabled' => true,
        'y' => [
          'formatter' => 'function (val) { return val + " billboards" }',
        ],
      ],
    ];
  }

  /**
   * Polling Interval
   * null, 1, 5, 10, 30 seconds or 1 minute
   *
   * @return string|null
   */
  protected function getPollingInterval(): ?string
  {
    return null;
  }

  /**
   * Filter Form Schema
   *
   * @return array|null
   */
  protected function getFilterForm(): ?array
  {
    return null;
  }
}
