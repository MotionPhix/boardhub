<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ContractExpirations extends TableWidget
{
  protected static ?int $sort = 3;

  protected int | string | array $columnSpan = 'full';

  protected static ?string $heading = 'Active Contract Status';

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Contract::query()
          ->where('agreement_status', 'active')
          ->whereHas('billboards', function ($query) {
            $query->where('billboard_contract.booking_status', BookingStatus::IN_USE->value);
          })
          ->orderBy('created_at', 'desc')
      )
      ->columns([
        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('total_amount')
          ->money()
          ->sortable(),

        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),

        Tables\Columns\TextColumn::make('start_date')
          ->dateTime()
          ->sortable()
          ->description(fn (Contract $record) =>
            'Started ' . $record->start_date?->diffForHumans()
          ),

        Tables\Columns\TextColumn::make('end_date')
          ->dateTime()
          ->sortable()
          ->description(fn (Contract $record) =>
          $record->end_date?->isPast()
            ? 'Expired ' . $record->end_date->diffForHumans()
            : 'Expires ' . $record->end_date?->diffForHumans()
          )
          ->color(fn (Contract $record): string =>
          $record->end_date?->isPast()
            ? 'danger'
            : ($record->end_date?->diffInDays(now()) <= 30
            ? 'warning'
            : 'success')
          ),

        Tables\Columns\TextColumn::make('last_booking_update')
          ->state(function (Contract $record) {
            return $record->billboards()
              ->join('billboard_contract', 'billboards.id', '=', 'billboard_contract.billboard_id')
              ->where('billboard_contract.contract_id', $record->id)
              ->orderBy('billboard_contract.updated_at', 'desc')
              ->value('billboard_contract.updated_at');
          })
          ->dateTime()
          ->sortable()
          ->description('Last booking update'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('booking_status')
          ->options(collect(BookingStatus::cases())->pluck('value', 'value'))
          ->query(function (Builder $query, array $data) {
            if (!$data['value']) return $query;

            return $query->whereHas('billboards', function ($query) use ($data) {
              $query->where('billboard_contract.booking_status', $data['value']);
            });
          }),

        Tables\Filters\Filter::make('expiring_soon')
          ->label('Expiring Within 30 Days')
          ->query(fn (Builder $query): Builder =>
          $query->whereDate('end_date', '<=', now()->addDays(30))
            ->whereDate('end_date', '>=', now())
          ),

        Tables\Filters\Filter::make('expired')
          ->query(fn (Builder $query): Builder =>
          $query->whereDate('end_date', '<', now())
          ),
      ])
      ->actions([
        Tables\Actions\Action::make('view')
          ->url(fn (Contract $record) => route('filament.admin.resources.contracts.view', $record))
          ->icon('heroicon-m-eye'),
        Tables\Actions\Action::make('edit')
          ->url(fn (Contract $record) => route('filament.admin.resources.contracts.edit', $record))
          ->icon('heroicon-m-pencil'),
      ])
      ->defaultSort('end_date', 'asc')
      ->paginated([10, 25, 50])
      ->poll('30s');
  }
}
