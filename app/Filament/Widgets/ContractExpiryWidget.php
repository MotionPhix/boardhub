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
      ->with(['billboards', 'client'])
      ->where('agreement_status', Contract::STATUS_ACTIVE)
      ->whereHas('billboards', function ($query) {
        $query->where('billboard_contract.booking_status', 'in_use');
      })
      ->where('end_date', '>', now())
      ->where('end_date', '<=', now()->addDays(30))
      ->orderBy('end_date');
  }

  protected function getTableColumns(): array
  {
    return [
      TextColumn::make('contract_number')
        ->label('Contract')
        ->searchable(),

      TextColumn::make('client.name')
        ->label('Client')
        ->searchable(),

      TextColumn::make('billboards_count')
        ->label('Billboards')
        ->counts('billboards')
        ->sortable(),

      TextColumn::make('contract_final_amount')
        ->label('Contract Value')
        ->money('MWK')
        ->sortable(),

      TextColumn::make('end_date')
        ->date()
        ->sortable()
        ->description(fn (Contract $record): string =>
          'Expires in ' . now()->diffInDays($record->end_date) . ' days'),

      TextColumn::make('status')
        ->badge()
        ->color(fn (string $state): string => match ($state) {
          'Active' => 'success',
          'Expiring Soon' => 'warning',
          'Expired' => 'danger',
          default => 'gray',
        }),
    ];
  }
}
