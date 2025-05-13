<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CityResource extends Resource
{
  protected static ?string $model = City::class;

  protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

  protected static ?string $navigationGroup = 'Location Management';

  protected static ?int $navigationSort = 3;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make()
          ->schema([
            Forms\Components\Select::make('country_code')
              ->label('Country')
              ->options(Country::query()
                ->where('is_active', true)
                ->pluck('name', 'code'))
              ->required()
              ->searchable()
              ->preload()
              ->live()
              ->afterStateUpdated(fn (Set $set) => $set('state_code', null)),

            Forms\Components\Select::make('state_code')
              ->label('State/Region')
              ->options(fn (Get $get): Collection => State::query()
                ->where('country_code', $get('country_code'))
                ->where('is_active', true)
                ->pluck('name', 'code'))
              ->required()
              ->searchable()
              ->preload()
              ->live()
              ->visible(fn (Get $get) => filled($get('country_code')))
              ->disabled(fn (Get $get) => !filled($get('country_code'))),

            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->placeholder('Enter city name'),

            Forms\Components\TextInput::make('code')
              ->required()
              ->maxLength(10)
              ->unique(ignoreRecord: true)
              ->placeholder('Enter city code')
              ->helperText('A unique code for the city (e.g., BT for Blantyre)'),

            Forms\Components\Toggle::make('is_active')
              ->label('Active')
              ->default(true)
              ->helperText('Inactive cities will not be available for selection'),
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

        Tables\Columns\TextColumn::make('state.name')
          ->label('State/Region')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('country.name')
          ->label('Country')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('locations_count')
          ->label('Locations')
          ->counts('locations'),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->relationship('country', 'name')
          ->preload()
          ->multiple()
          ->searchable(),

        Tables\Filters\SelectFilter::make('state')
          ->relationship('state', 'name')
          ->preload()
          ->multiple()
          ->searchable(),

        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active')
          ->boolean()
          ->trueLabel('Active only')
          ->falseLabel('Inactive only')
          ->placeholder('All'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->before(function ($record) {
            // Prevent deletion if city has locations
            if ($record->locations()->count() > 0) {
              throw new \Exception('Cannot delete city with existing locations.');
            }
          }),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->before(function ($records) {
              // Prevent deletion if any city has locations
              foreach ($records as $record) {
                if ($record->locations()->count() > 0) {
                  throw new \Exception("Cannot delete city '{$record->name}' with existing locations.");
                }
              }
            }),
        ]),
      ])
      ->defaultSort('name', 'asc');
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCities::route('/'),
      'create' => Pages\CreateCity::route('/create'),
      'edit' => Pages\EditCity::route('/{record}/edit'),
    ];
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }

  public static function getGloballySearchableAttributes(): array
  {
    return ['name', 'code', 'country.name', 'state.name'];
  }
}
