<?php

namespace App\Filament\Resources\StateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CitiesRelationManager extends RelationManager
{
  protected static string $relationship = 'cities';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255),

        Forms\Components\TextInput::make('code')
          ->required()
          ->maxLength(10)
          ->unique(ignoreRecord: true),

        Forms\Components\Toggle::make('is_active')
          ->label('Active')
          ->default(true),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        Tables\Columns\TextColumn::make('name'),
        Tables\Columns\TextColumn::make('code'),
        Tables\Columns\IconColumn::make('is_active')
          ->boolean(),
      ])
      ->filters([
        //
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
      ]);
  }
}
