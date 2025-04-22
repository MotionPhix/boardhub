<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ContractExpirations extends BaseWidget
{
  protected static ?int $sort = 3;

  protected int | string | array $columnSpan = 'full';

  protected static ?string $heading = 'Active Contract Status';

  protected function getTableQuery(): Builder
  {
    return Contract::query()
      ->where('agreement_status', 'active')
      ->whereHas('billboards', function ($query) {
        $query->wherePivot('booking_status', BookingStatus::IN_USE->value);
      })
      ->orderBy('created_at', 'desc');
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('client.name')
          ->searchable(),
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->money(),
        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->description(fn (Contract $record) =>
            'Active for ' . $record->created_at->diffForHumans(null, true)
          ),
        Tables\Columns\TextColumn::make('last_updated')
          ->state(function (Contract $record) {
            $lastBooking = $record->billboards()
              ->orderByPivot('updated_at', 'desc')
              ->first();

            return $lastBooking?->pivot->updated_at;
          })
          ->dateTime()
          ->sortable()
          ->description(fn (Contract $record) => 'Last booking update'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('booking_status')
          ->options(collect(BookingStatus::cases())->pluck('value', 'value'))
          ->query(function (Builder $query, array $data) {
            if (!$data['value']) return $query;

            return $query->whereHas('billboards', function ($query) use ($data) {
              $query->wherePivot('booking_status', $data['value']);
            });
          }),
      ])
      ->actions([
        Tables\Actions\Action::make('view')
          ->url(fn (Contract $record) => route('filament.admin.resources.contracts.view', $record))
          ->icon('heroicon-m-eye'),
        Tables\Actions\Action::make('edit')
          ->url(fn (Contract $record) => route('filament.admin.resources.contracts.edit', $record))
          ->icon('heroicon-m-pencil'),
      ])
      ->defaultSort('created_at', 'desc')
      ->paginated([10, 25, 50])
      ->poll('30s');
  }
}
