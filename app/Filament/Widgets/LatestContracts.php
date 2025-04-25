<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestContracts extends BaseWidget
{
  protected static ?int $sort = 3;

  protected int | string | array $columnSpan = 'full';

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Contract::query()
          ->latest()
          ->with(['billboards.location', 'client'])
          ->limit(5)
      )
      ->columns([
        Tables\Columns\TextColumn::make('client.name')
          ->label('Client')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('billboards.name')
          ->label('Billboards')
          ->listWithLineBreaks()
          ->searchable(),

        Tables\Columns\TextColumn::make('start_date')
          ->dateTime()
          ->sortable(),

        Tables\Columns\TextColumn::make('end_date')
          ->dateTime()
          ->sortable(),

        Tables\Columns\TextColumn::make('total_amount')
          ->money('MWK')
          ->sortable(),

        Tables\Columns\BadgeColumn::make('agreement_status')
          ->colors([
            'success' => 'active',
            'warning' => 'pending',
            'danger' => 'expired',
          ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
