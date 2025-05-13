<?php

namespace App\Filament\Resources\CountryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatesRelationManager extends RelationManager
{
  protected static string $relationship = 'states';

  protected static ?string $recordTitleAttribute = 'name';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('code')
          ->required()
          ->maxLength(10)
          ->unique(ignoreRecord: true)
          ->helperText('For example: MW-N for Northern Region, Malawi')
          ->placeholder('Enter state/region code'),

        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255)
          ->placeholder('Enter state/region name'),

        Forms\Components\Toggle::make('is_active')
          ->label('Active')
          ->default(true)
          ->helperText('Inactive states will not be available for selection'),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('code')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('cities_count')
          ->label('Cities')
          ->counts('cities'),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active')
          ->boolean()
          ->trueLabel('Active only')
          ->falseLabel('Inactive only')
          ->placeholder('All'),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->after(function ($record) {
            // Set the country_code automatically
            $record->country_code = $this->getOwnerRecord()->code;
            $record->save();
          }),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->before(function ($record) {
            // Prevent deletion if state has cities
            if ($record->cities()->count() > 0) {
              throw new \Exception('Cannot delete state with existing cities.');
            }
          }),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->before(function ($records) {
              // Prevent deletion if any state has cities
              foreach ($records as $record) {
                if ($record->cities()->count() > 0) {
                  throw new \Exception("Cannot delete state '{$record->name}' with existing cities.");
                }
              }
            }),
        ]),
      ]);
  }
}
