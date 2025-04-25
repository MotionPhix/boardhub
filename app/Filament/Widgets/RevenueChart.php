<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
  protected static ?string $heading = 'Revenue Over Time';

  protected static ?int $sort = 4;

  protected function getData(): array
  {
    $data = Contract::where('agreement_status', 'active')
      ->whereBetween('start_date', [
        Carbon::now()->startOfYear(),
        Carbon::now()->endOfYear(),
      ])
      ->selectRaw('MONTH(start_date) as month, SUM(total_amount) as total')
      ->groupBy('month')
      ->orderBy('month')
      ->get();

    $months = collect(range(1, 12))->map(function ($month) use ($data) {
      $monthData = $data->firstWhere('month', $month);
      return $monthData ? $monthData->total : 0;
    });

    return [
      'datasets' => [
        [
          'label' => 'Revenue',
          'data' => $months->toArray(),
          'fill' => 'start',
          'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
          'borderColor' => 'rgb(59, 130, 246)',
        ],
      ],
      'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    ];
  }

  protected function getType(): string
  {
    return 'line';
  }
}
