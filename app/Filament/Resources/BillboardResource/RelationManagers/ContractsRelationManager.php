<?php

namespace App\Filament\Resources\BillboardResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractsRelationManager extends RelationManager
{
  protected static string $relationship = 'contracts';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Contract Details')
              ->schema([
                Forms\Components\Select::make('client_id')
                  ->relationship('client', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                      ->email()
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                      ->tel()
                      ->maxLength(255),
                  ]),
                Forms\Components\TextInput::make('contract_number')
                  ->default(fn () => 'CNT-' . date('Y') . '-' . str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT))
                  ->disabled()
                  ->dehydrated(),
                Forms\Components\TextInput::make('total_amount')
                  ->numeric()
                  ->prefix('MK')
                  ->required()
                  ->maxValue(42949672.95),
                Forms\Components\Select::make('agreement_status')
                  ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                  ])
                  ->required()
                  ->default('draft'),
              ])
              ->columns(2),

            Forms\Components\Section::make('Booking Details')
              ->schema([
                Forms\Components\TextInput::make('pivot.price')
                  ->numeric()
                  ->prefix('MK')
                  ->required()
                  ->label('Billboard Price'),
                Forms\Components\Select::make('pivot.booking_status')
                  ->options(collect(BookingStatus::cases())->pluck('value', 'value'))
                  ->required()
                  ->default(BookingStatus::PENDING->value)
                  ->label('Booking Status'),
                Forms\Components\Textarea::make('pivot.notes')
                  ->maxLength(65535)
                  ->columnSpanFull()
                  ->label('Booking Notes'),
              ])
              ->columns(2),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Documents')
              ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('contract_documents')
                  ->collection('contract_documents')
                  ->multiple()
                  ->maxFiles(5)
                  ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                  ->columnSpanFull(),

                Forms\Components\SpatieMediaLibraryFileUpload::make('signed_contract')
                  ->collection('signed_contracts')
                  ->maxFiles(1)
                  ->acceptedFileTypes(['application/pdf'])
                  ->columnSpanFull(),
              ])
              ->collapsible(),
          ])
          ->columnSpan(['lg' => 1]),
      ])
      ->columns(3);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('contract_number')
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->money()
          ->sortable(),
        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->colors([
            'danger' => 'cancelled',
            'warning' => 'draft',
            'success' => 'active',
            'gray' => 'completed',
          ]),
        Tables\Columns\TextColumn::make('pivot.price')
          ->money()
          ->label('Billboard Price')
          ->sortable(),
        Tables\Columns\TextColumn::make('pivot.booking_status')
          ->badge()
          ->color(fn (string $state) => match ($state) {
            BookingStatus::PENDING->value => 'warning',
            BookingStatus::CONFIRMED->value => 'info',
            BookingStatus::IN_USE->value => 'success',
            BookingStatus::COMPLETED->value => 'gray',
            BookingStatus::CANCELLED->value => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn (string $state) => BookingStatus::from($state)->label()),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('agreement_status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ]),
        Tables\Filters\SelectFilter::make('booking_status')
          ->options(collect(BookingStatus::cases())->pluck('value', 'value')),
        Tables\Filters\Filter::make('active')
          ->query(fn (Builder $query): Builder => $query
            ->where('agreement_status', 'active')
            ->wherePivot('booking_status', BookingStatus::IN_USE->value))
          ->toggle(),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->after(function ($data, $record) {
            // After creating the contract, update the billboard's physical_status if needed
            if ($record->agreement_status === 'active') {
              $this->ownerRecord->update([
                'physical_status' => 'operational',
              ]);
            }
          }),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Booking Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('booking_status')
                ->label('New Booking Status')
                ->options(collect(BookingStatus::cases())->pluck('value', 'value'))
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
