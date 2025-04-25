<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class BillboardOccupancyChart extends ApexChartWidget
{
  /**
   * Chart Id
   *
   * @var string|null
   */
  protected static ?string $chartId = 'billboardOccupancy';

  /**
   * Widget Title
   *
   * @var string|null
   */
  protected static ?string $heading = 'Billboard Occupancy';

  /**
   * Chart options (series, labels, types, size, animations...)
   * https://apexcharts.com/docs/options
   *
   * @return array
   */
  protected function getOptions(): array
  {
    $total = Billboard::count();
    $occupied = Billboard::whereHas('contracts', function ($query) {
      $query->where('agreement_status', 'active');
    })->count();
    $available = $total - $occupied;

    return [
      'chart' => [
        'type' => 'donut',
        'height' => 300,
      ],
      'series' => [$occupied, $available],
      'labels' => ['Occupied', 'Available'],
      'colors' => ['#0891b2', '#64748b'],
      'legend' => [
        'position' => 'bottom',
      ],
      'plotOptions' => [
        'pie' => [
          'donut' => [
            'size' => '70%',
            'labels' => [
              'show' => true,
              'name' => [
                'show' => true,
              ],
              'value' => [
                'show' => true,
                'formatter' => 'function (val) { return val }',
              ],
              'total' => [
                'show' => true,
                'formatter' => 'function (w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0) }',
              ],
            ],
          ],
        ],
      ],
      'dataLabels' => [
        'enabled' => false,
      ],
      'tooltip' => [
        'enabled' => true,
        'y' => [
          'formatter' => 'function(value) { return value + " Billboards" }',
        ],
      ],
      'stroke' => [
        'width' => 0,
      ],
      'responsive' => [
        [
          'breakpoint' => 480,
          'options' => [
            'chart' => [
              'height' => 250,
            ],
            'legend' => [
              'position' => 'bottom',
            ],
          ],
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

  /**
   * Widget Sort Order
   */
  protected static ?int $sort = 5;

  /**
   * Widget Column Span
   */
  protected int | string | array $columnSpan = 'full';
}
