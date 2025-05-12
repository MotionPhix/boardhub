<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CityResource extends Resource
{
  protected static ?string $model = City::class;
  protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
  protected static ?string $navigationGroup = 'Management';

  public static function form(Forms\Form $form): Forms\Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255),

        Forms\Components\TextInput::make('code')
          ->maxLength(10)
          ->unique(ignoreRecord: true)
          ->helperText('Leave empty to auto-generate from name')
          ->placeholder('Auto-generated if empty')
          ->uppercase(),

        Forms\Components\Select::make('country_code')
          ->relationship('country', 'name')
          ->preload()
          ->searchable()
          ->required(),

        Forms\Components\TextInput::make('state')
          ->maxLength(255),

        Forms\Components\Toggle::make('is_active')
          ->label('Active')
          ->default(true),

        Forms\Components\Textarea::make('description')
          ->maxLength(65535)
          ->columnSpanFull(),
      ]);
  }

  public static function table(Tables\Table $table): Tables\Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('code')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('country.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('state')
          ->searchable()
          ->sortable()
          ->toggleable(),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->relationship('country', 'name')
          ->preload()
          ->multiple(),

        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active')
          ->default(true),
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

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCities::route('/'),
      'create' => Pages\CreateCity::route('/create'),
      'edit' => Pages\EditCity::route('/{record}/edit'),
    ];
  }

  public static function getGloballySearchableAttributes(): array
  {
    return ['name', 'code', 'state', 'country.name'];
  }
}
