<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PopularLocationsWidget extends TableWidget
{
  protected static ?string $heading = 'Most Popular Locations';

  protected static ?int $sort = 3;

  protected int | string | array $columnSpan = 1;

  protected function getTableQuery(): Builder
  {
    return Location::withCount(['billboards' => function ($query) {
      $query->whereHas('contracts', function ($q) {
        $q->where('booking_status', 'in_use');
      });
    }])
      ->orderByDesc('billboards_count')
      ->limit(5);
  }

  protected function getTableColumns(): array
  {
    return [
      TextColumn::make('name')
        ->description(fn (Location $record): string => $record->full_address),

      TextColumn::make('billboards_count')
        ->label('Active Billboards')
        ->sortable(),
    ];
  }
}
