<?php

namespace App\Filament\Resources\LocationResource\RelationManagers;

use App\Models\Billboard;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BillboardsRelationManager extends RelationManager
{
  protected static string $relationship = 'billboards';

  protected static ?string $recordTitleAttribute = 'name';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('name')
          ->label('Site')
          ->required()
          ->maxLength(255)
          ->columnSpanFull(),

        Forms\Components\TextInput::make('size')
          ->helperText('The format should be Wm x Hm e.g. 12m x 6m')
          ->required(),

        Forms\Components\Select::make('physical_status')
          ->options(Billboard::getPhysicalStatuses())
          ->required()
          ->default(Billboard::PHYSICAL_STATUS_OPERATIONAL)
          ->native(false) // Uses custom select instead of native browser select
          ->createOptionAction(function (Forms\Components\Actions\Action $action) {
            dd($action);
          })
          ->prefixIcon('heroicon-m-wrench-screwdriver')
          /*  Billboard::PHYSICAL_STATUS_OPERATIONAL => 'heroicon-m-check-circle',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'heroicon-m-wrench-screwdriver',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'heroicon-m-exclamation-triangle',
            Billboard::PHYSICAL_STATUS_REMOVED => 'heroicon-m-archive-box-x-mark',
            Billboard::PHYSICAL_STATUS_STOLEN => 'heroicon-m-shield-exclamation',
          ])*/,

        Forms\Components\TextInput::make('base_price')
          ->label('Booking Fee')
          ->required()
          ->numeric()
          ->prefix(fn($record) => $record?->currency_code
            ?? Settings::getDefaultCurrency()['code']
            ?? 'MWK'),

        Forms\Components\Select::make('currency_code')
          ->label('Currency')
          ->options(fn() => collect(Settings::getAvailableCurrencies())
            ->pluck('code', 'code')
            ->toArray())
          ->default(fn() => Settings::getDefaultCurrency()['code'] ?? 'MWK')
          ->required(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->label('Site')
          ->searchable(),

        Tables\Columns\TextColumn::make('size')
          ->searchable(),

        Tables\Columns\TextColumn::make('base_price')
          ->label('Booking Fee')
          ->money(fn($record) => $record->currency_code
            ?? Settings::getDefaultCurrency()['code'] ?? 'MWK')
          ->sortable(),

        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->formatStateUsing(fn(string $state): string => match ($state) {
            'operational' => 'Operational',
            'maintenance' => 'Under Maintenance',
            'damaged' => 'Damaged',
            'removed' => 'Removed',
            'stolen' => 'Stolen',
          })
          ->icon(fn (string $state): string => match (strtolower($state)) {
            Billboard::PHYSICAL_STATUS_OPERATIONAL => 'heroicon-m-check-circle',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'heroicon-m-wrench-screwdriver',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'heroicon-m-exclamation-triangle',
            Billboard::PHYSICAL_STATUS_REMOVED => 'heroicon-m-archive-box-x-mark',
            Billboard::PHYSICAL_STATUS_STOLEN => 'heroicon-m-shield-exclamation',
            default => 'heroicon-m-question-mark-circle',
          })
          ->colors([
            'success' => fn ($state) => strtolower($state) === Billboard::PHYSICAL_STATUS_OPERATIONAL,
            'warning' => fn ($state) => strtolower($state) === Billboard::PHYSICAL_STATUS_MAINTENANCE,
            'danger' => fn ($state) => in_array(strtolower($state), [
              Billboard::PHYSICAL_STATUS_DAMAGED,
              Billboard::PHYSICAL_STATUS_STOLEN
            ]),
            'gray' => fn ($state) => strtolower($state) === Billboard::PHYSICAL_STATUS_REMOVED,
          ]),

        Tables\Columns\TextColumn::make('current_contract.contract_number')
          ->label('Active Contract')
          ->placeholder('Not on contract'),
      ])
      ->description('List of all billboards in this location')
      ->filters([
        Tables\Filters\SelectFilter::make('physical_status')
          ->options([
            'operational' => 'Operational',
            'maintenance' => 'Under Maintenance',
            'damaged' => 'Damaged',
            'removed' => 'Removed',
            'stolen' => 'Stolen'
          ]),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make(),
      ])
      ->actions([
        Tables\Actions\ActionGroup::make([
          Tables\Actions\EditAction::make(),
          Tables\Actions\DeleteAction::make(),
        ]),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
