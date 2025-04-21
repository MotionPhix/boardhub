<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestClients extends BaseWidget
{
  protected static ?int $sort = 2;

  protected int | string | array $columnSpan = 'full';

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Client::latest()->limit(5)
      )
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextColumn::make('email')
          ->searchable(),
        Tables\Columns\TextColumn::make('company'),
        Tables\Columns\TextColumn::make('active_contracts_count')
          ->label('Active Contracts')
          ->counts('contracts', fn ($query) => $query
            ->where('status', 'active')
            ->where('end_date', '>=', now())
          ),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime(),
      ]);
  }
}
