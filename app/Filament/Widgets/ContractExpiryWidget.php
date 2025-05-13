<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ContractExpiryWidget extends TableWidget
{
  protected static ?string $heading = 'Contracts Expiring Soon';

  protected static ?int $sort = 4;

  protected int | string | array $columnSpan = 'full';

  protected function getTableQuery(): Builder
  {
    return Contract::query()
      ->with(['billboard.location', 'customer'])
      ->where('booking_status', 'in_use')
      ->where('end_date', '>', now())
      ->where('end_date', '<=', now()->addDays(30))
      ->orderBy('end_date');
  }

  protected function getTableColumns(): array
  {
    return [
      TextColumn::make('billboard.location.name')
        ->label('Location')
        ->searchable(),
      TextColumn::make('billboard.name')
        ->label('Billboard')
        ->searchable(),
      TextColumn::make('customer.name')
        ->label('Customer')
        ->searchable(),
      TextColumn::make('end_date')
        ->date()
        ->sortable()
        ->description(fn (Contract $record): string =>
          'Expires in ' . now()->diffInDays($record->end_date) . ' days'),
    ];
  }
}
