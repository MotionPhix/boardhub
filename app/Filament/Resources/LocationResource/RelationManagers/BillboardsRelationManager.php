<?php

namespace App\Filament\Resources\LocationResource\RelationManagers;

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
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('size')
          ->required(),
        Forms\Components\TextInput::make('type')
          ->required(),
        Forms\Components\TextInput::make('base_price')
          ->required()
          ->numeric()
          ->prefix('MK'),
        Forms\Components\Select::make('physical_status')
          ->options([
            'operational' => 'Operational',
            'maintenance' => 'Under Maintenance',
            'damaged' => 'Damaged',
          ])
          ->required()
          ->default('operational'),
        Forms\Components\Textarea::make('description')
          ->maxLength(65535)
          ->columnSpanFull(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextColumn::make('size')
          ->searchable(),
        Tables\Columns\TextColumn::make('type')
          ->searchable(),
        Tables\Columns\TextColumn::make('base_price')
          ->money()
          ->sortable(),
        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->colors([
            'success' => 'operational',
            'warning' => 'maintenance',
            'danger' => 'damaged',
          ]),
        Tables\Columns\TextColumn::make('current_contract.contract_number')
          ->label('Active Contract')
          ->placeholder('-'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('physical_status')
          ->options([
            'operational' => 'Operational',
            'maintenance' => 'Under Maintenance',
            'damaged' => 'Damaged',
          ]),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make(),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
