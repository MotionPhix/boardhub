<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Models\Billboard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillboardsRelationManager extends RelationManager
{
  protected static string $relationship = 'billboards';

  protected static ?string $title = 'Contract Billboards';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Select::make('billboard_id')
          ->label('Billboard')
          ->options(Billboard::query()
            ->whereDoesntHave('contracts', function ($query) {
              $query->where('agreement_status', 'active')
                ->wherePivot('booking_status', 'in_use');
            })
            ->orWhereHas('contracts', function ($query) {
              $query->where('contract_id', $this->ownerRecord->id);
            })
            ->pluck('name', 'id'))
          ->searchable()
          ->preload()
          ->required()
          ->live()
          ->afterStateUpdated(function ($state, Forms\Set $set) {
            if ($state) {
              $billboard = Billboard::find($state);
              $set('price', $billboard?->base_price ?? 0);
            }
          }),

        Forms\Components\TextInput::make('price')
          ->label('Booking Price')
          ->numeric()
          ->prefix('MK')
          ->required()
          ->maxValue(42949672.95),

        Forms\Components\Select::make('booking_status')
          ->options([
            BookingStatus::PENDING->value => 'Pending',
            BookingStatus::IN_USE->value => 'In Use',
            BookingStatus::COMPLETED->value => 'Completed',
            BookingStatus::CANCELLED->value => 'Cancelled',
          ])
          ->default(BookingStatus::PENDING->value)
          ->required(),

        Forms\Components\Textarea::make('notes')
          ->maxLength(65535)
          ->columnSpanFull(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('location')
          ->searchable(),
        Tables\Columns\TextColumn::make('dimensions')
          ->searchable(),
        Tables\Columns\TextColumn::make('price')
          ->money()
          ->sortable(),
        Tables\Columns\TextColumn::make('booking_status')
          ->badge()
          ->colors([
            'danger' => 'cancelled',
            'warning' => 'pending',
            'success' => 'in_use',
            'gray' => 'completed',
          ]),
        Tables\Columns\TextColumn::make('pivot.created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('booking_status')
          ->options([
            BookingStatus::PENDING->value => 'Pending',
            BookingStatus::IN_USE->value => 'In Use',
            BookingStatus::COMPLETED->value => 'Completed',
            BookingStatus::CANCELLED->value => 'Cancelled',
          ]),
      ])
      ->headerActions([
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->form(fn (Tables\Actions\AttachAction $action): array => [
            $action->getRecordSelect()
              ->label('Billboard')
              ->options(Billboard::query()
                ->whereDoesntHave('contracts', function ($query) {
                  $query->where('agreement_status', 'active')
                    ->wherePivot('booking_status', 'in_use');
                })
                ->orWhereHas('contracts', function ($query) {
                  $query->where('contract_id', $this->ownerRecord->id);
                })
                ->pluck('name', 'id'))
              ->searchable()
              ->preload()
              ->required()
              ->live()
              ->afterStateUpdated(function ($state, Forms\Set $set) {
                if ($state) {
                  $billboard = Billboard::find($state);
                  $set('price', $billboard?->base_price ?? 0);
                }
              }),
            Forms\Components\TextInput::make('price')
              ->label('Booking Price')
              ->numeric()
              ->prefix('MK')
              ->required()
              ->maxValue(42949672.95),
            Forms\Components\Select::make('booking_status')
              ->options([
                BookingStatus::PENDING->value => 'Pending',
                BookingStatus::IN_USE->value => 'In Use',
                BookingStatus::COMPLETED->value => 'Completed',
                BookingStatus::CANCELLED->value => 'Cancelled',
              ])
              ->default($this->ownerRecord->agreement_status === 'active'
                ? BookingStatus::IN_USE->value
                : BookingStatus::PENDING->value)
              ->required(),
          ]),
      ])
      ->actions([
        Tables\Actions\EditAction::make()
          ->form([
            Forms\Components\TextInput::make('price')
              ->label('Booking Price')
              ->numeric()
              ->prefix('MK')
              ->required()
              ->maxValue(42949672.95),
            Forms\Components\Select::make('booking_status')
              ->options([
                BookingStatus::PENDING->value => 'Pending',
                BookingStatus::IN_USE->value => 'In Use',
                BookingStatus::COMPLETED->value => 'Completed',
                BookingStatus::CANCELLED->value => 'Cancelled',
              ])
              ->required(),
            Forms\Components\Textarea::make('notes')
              ->maxLength(65535)
              ->columnSpanFull(),
          ]),
        Tables\Actions\DetachAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Booking Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('booking_status')
                ->label('New Booking Status')
                ->options([
                  BookingStatus::PENDING->value => 'Pending',
                  BookingStatus::IN_USE->value => 'In Use',
                  BookingStatus::COMPLETED->value => 'Completed',
                  BookingStatus::CANCELLED->value => 'Cancelled',
                ])
                ->required(),
            ])
            ->action(function (array $data, $records) {
              $records->each(function ($record) use ($data) {
                $record->pivot->update([
                  'booking_status' => $data['booking_status'],
                ]);
              });
            }),
        ]),
      ]);
  }
}
