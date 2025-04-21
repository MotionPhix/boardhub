<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ContractExpirations extends BaseWidget
{
  protected static ?int $sort = 3;

  protected int | string | array $columnSpan = 'full';

  protected static ?string $heading = 'Upcoming Contract Expirations';

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Contract::query()
          ->where('status', 'active')
          ->where('end_date', '>=', now())
          ->where('end_date', '<=', now()->addDays(30))
          ->orderBy('end_date')
      )
      ->columns([
        Tables\Columns\TextColumn::make('client.name')
          ->searchable(),
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable(),
        Tables\Columns\TextColumn::make('end_date')
          ->date()
          ->sortable()
          ->description(fn (Contract $record) =>
            'Expires in ' . now()->diffInDays($record->end_date) . ' days'
          ),
        Tables\Columns\TextColumn::make('total_amount')
          ->money(),
        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),
      ])
      ->actions([
        Tables\Actions\Action::make('view')
          ->url(fn (Contract $record) => route('filament.admin.resources.contracts.edit', $record))
          ->icon('heroicon-m-eye'),
      ]);
  }
}
