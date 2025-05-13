<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StateResource\Pages;
use App\Filament\Resources\StateResource\RelationManagers;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StateResource extends Resource
{
  protected static ?string $model = State::class;

  protected static ?string $navigationIcon = 'heroicon-o-map';

  protected static ?string $navigationGroup = 'Location Management';

  protected static ?int $navigationSort = 2;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make()
          ->schema([
            Forms\Components\Select::make('country_code')
              ->label('Country')
              ->relationship('country', 'name')
              ->required()
              ->searchable()
              ->preload()
              ->createOptionForm([
                Forms\Components\TextInput::make('code')
                  ->required()
                  ->maxLength(2)
                  ->unique('countries', 'code'),
                Forms\Components\TextInput::make('name')
                  ->required()
                  ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                  ->default(true),
              ]),

            Forms\Components\TextInput::make('code')
              ->required()
              ->maxLength(10)
              ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),

            Forms\Components\Toggle::make('is_active')
              ->label('Active')
              ->default(true),
          ])
          ->columns(2),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('country.name')
          ->label('Country')
          ->sortable()
          ->searchable(),

        Tables\Columns\TextColumn::make('name')
          ->searchable(),

        Tables\Columns\TextColumn::make('code')
          ->searchable(),

        Tables\Columns\TextColumn::make('cities_count')
          ->label('Cities')
          ->counts('cities'),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->relationship('country', 'name')
          ->preload()
          ->multiple(),

        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active')
          ->boolean()
          ->trueLabel('Active')
          ->falseLabel('Inactive')
          ->placeholder('All'),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\CitiesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListStates::route('/'),
      'create' => Pages\CreateState::route('/create'),
      'edit' => Pages\EditState::route('/{record}/edit'),
    ];
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }
}
