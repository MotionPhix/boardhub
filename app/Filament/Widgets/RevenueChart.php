<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueChart extends ApexChartWidget
{
  /**
   * Chart Id
   *
   * @var string|null
   */
  protected static ?string $chartId = 'revenueChart';

  /**
   * Widget Title
   *
   * @var string|null
   */
  protected static ?string $heading = 'Revenue Trends';

  protected int | string | array $columnSpan = 'full';

  protected static ?int $sort = 4;

  public static function canView(): bool
  {
    return auth()->user()->hasRole(['super_admin', 'admin']);
  }

  /**
   * Chart options (series, labels, types, size, animations...)
   * https://apexcharts.com/docs/options
   *
   * @return array
   */
  protected function getOptions(): array
  {
    $data = Contract::query()
      ->where('agreement_status', 'active')
      ->whereBetween('start_date', [
        Carbon::now()->startOfYear(),
        Carbon::now()->endOfYear(),
      ])
      ->selectRaw('MONTH(start_date) as month, SUM(total_amount) as total')
      ->groupBy('month')
      ->orderBy('month')
      ->get();

    $revenues = collect();
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Fill in all months with 0 if no data
    for ($month = 1; $month <= 12; $month++) {
      $monthData = $data->firstWhere('month', $month);
      $revenues->push($monthData ? round($monthData->total, 2) : 0);
    }

    return [
      'chart' => [
        'type' => 'area',
        'height' => 300,
        'toolbar' => [
          'show' => true,
        ],
        'animations' => [
          'enabled' => true,
          'easing' => 'easeinout',
        ],
      ],
      'series' => [
        [
          'name' => 'Revenue',
          'data' => $revenues->toArray(),
        ],
      ],
      'xaxis' => [
        'categories' => $months,
        'labels' => [
          'style' => [
            'fontFamily' => 'inherit',
            'fontWeight' => 600,
          ],
        ],
      ],
      'yaxis' => [
        'labels' => [
          'style' => [
            'fontFamily' => 'inherit',
          ],
          'formatter' => 'function (value) {
                        return "MK " + value.toLocaleString()
                    }',
        ],
      ],
      'stroke' => [
        'curve' => 'smooth',
        'width' => 2,
      ],
      'fill' => [
        'type' => 'gradient',
        'gradient' => [
          'shade' => 'dark',
          'type' => 'vertical',
          'shadeIntensity' => 0.5,
          'opacityFrom' => 0.7,
          'opacityTo' => 0.2,
        ],
      ],
      'colors' => ['#0ea5e9'],
      'dataLabels' => [
        'enabled' => false,
      ],
      'grid' => [
        'borderColor' => '#f3f4f6',
        'strokeDashArray' => 4,
        'xaxis' => [
          'lines' => [
            'show' => true,
          ],
        ],
      ],
      'tooltip' => [
        'enabled' => true,
        'y' => [
          'formatter' => 'function (value) {
                        return "MK " + value.toLocaleString()
                    }',
        ],
      ],
    ];
  }

  protected static function getComponentPath(): string
  {
    return 'filament.widgets.revenue-chart';
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
}
