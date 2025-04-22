<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

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
            Forms\Components\Section::make('Contract Information')
              ->schema([
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
                Forms\Components\Textarea::make('notes')
                  ->maxLength(65535)
                  ->columnSpanFull(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Billboards')
              ->schema([
                Forms\Components\Select::make('billboards')
                  ->relationship('billboards')
                  ->multiple()
                  ->preload()
                  ->searchable()
                  ->required(),
              ]),
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
          ->searchable(),
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
        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('agreement_status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ]),
        Tables\Filters\Filter::make('active')
          ->query(fn (Builder $query): Builder => $query
            ->where('agreement_status', 'active')
            ->whereHas('billboards', function ($query) {
              $query->wherePivot('booking_status', 'in_use');
            }))
          ->label('Currently Active')
          ->toggle(),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->after(function ($data, $record) {
            // After creating the contract, update the booking status for each billboard
            foreach ($record->billboards as $billboard) {
              $billboard->pivot->update([
                'booking_status' => $data['agreement_status'] === 'active'
                  ? BookingStatus::IN_USE->value
                  : BookingStatus::PENDING->value,
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
            ->label('Update Agreement Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('agreement_status')
                ->label('New Agreement Status')
                ->options([
                  'draft' => 'Draft',
                  'active' => 'Active',
                  'completed' => 'Completed',
                  'cancelled' => 'Cancelled',
                ])
                ->required(),
            ])
            ->action(function (array $data, $records) {
              $records->each(function ($record) use ($data) {
                $record->update(['agreement_status' => $data['agreement_status']]);

                // Update booking status for all billboards
                foreach ($record->billboards as $billboard) {
                  $billboard->pivot->update([
                    'booking_status' => $data['agreement_status'] === 'active'
                      ? BookingStatus::IN_USE->value
                      : ($data['agreement_status'] === 'completed'
                        ? BookingStatus::COMPLETED->value
                        : BookingStatus::CANCELLED->value),
                  ]);
                }
              });
            }),
        ]),
      ]);
  }
}
