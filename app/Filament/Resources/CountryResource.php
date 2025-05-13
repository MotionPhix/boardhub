<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Filament\Resources\CountryResource\RelationManagers\StatesRelationManager;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
  protected static ?string $model = Country::class;

  protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

  protected static ?string $navigationGroup = 'Location Management';

  protected static ?int $navigationSort = 1;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make()
          ->schema([
            Forms\Components\TextInput::make('code')
              ->required()
              ->maxLength(2)
              ->unique(ignoreRecord: true)
              ->helperText('Two letter ISO country code')
              ->placeholder('e.g., MW for Malawi'),

            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->placeholder('Enter country name'),

            Forms\Components\Toggle::make('is_active')
              ->label('Active')
              ->default(true)
              ->helperText('Inactive countries will not be available for selection'),

            Forms\Components\Toggle::make('is_default')
              ->label('Set as Default')
              ->default(false)
              ->helperText('Default country will be pre-selected in forms'),
          ])
          ->columns(2),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('code')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('states_count')
          ->label('States')
          ->counts('states'),

        Tables\Columns\TextColumn::make('cities_count')
          ->label('Cities')
          ->counts('cities'),

        Tables\Columns\IconColumn::make('is_default')
          ->label('Default')
          ->boolean()
          ->sortable(),

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

        Tables\Filters\TernaryFilter::make('is_default')
          ->label('Default')
          ->trueLabel('Default only')
          ->falseLabel('Non-default only')
          ->placeholder('All'),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->before(function ($record) {
            // Prevent deletion if country has states
            if ($record->states()->count() > 0) {
              throw new \Exception('Cannot delete country with existing states.');
            }
          }),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->before(function ($records) {
              // Prevent deletion if any country has states
              foreach ($records as $record) {
                if ($record->states()->count() > 0) {
                  throw new \Exception("Cannot delete country '{$record->name}' with existing states.");
                }
              }
            }),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      StatesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCountries::route('/'),
      'create' => Pages\CreateCountry::route('/create'),
      'edit' => Pages\EditCountry::route('/{record}/edit'),
    ];
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }
}
