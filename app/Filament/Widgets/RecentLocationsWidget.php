<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLocationsWidget extends TableWidget
{
  protected static ?string $heading = 'Recently Added Locations';

  protected static ?int $sort = 2;

  protected int | string | array $columnSpan = 1;

  protected function getTableQuery(): Builder
  {
    return Location::with(['city', 'state', 'country'])
      ->latest()
      ->limit(5);
  }

  protected function getTableColumns(): array
  {
    return [
      TextColumn::make('name')
        ->searchable()
        ->description(fn (Location $record): string => $record->full_address),
      TextColumn::make('created_at')
        ->dateTime()
        ->sortable(),
    ];
  }
}
