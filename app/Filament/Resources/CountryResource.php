<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CountryResource extends Resource
{
  protected static ?string $model = Country::class;
  protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
  protected static ?string $navigationGroup = 'Management';

  public static function form(Forms\Form $form): Forms\Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('code')
          ->required()
          ->maxLength(2)
          ->unique(ignoreRecord: true)
          ->helperText('Two-letter ISO country code (e.g., MW, ZM)')
          ->uppercase(),

        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255),

        Forms\Components\Toggle::make('is_active')
          ->label('Active')
          ->default(true),

        Forms\Components\Toggle::make('is_default')
          ->label('Set as Default')
          ->default(false)
          ->afterStateUpdated(function ($state, Forms\Set $set) {
            if ($state) {
              // When setting a country as default, update database
              Country::query()
                ->where('is_default', true)
                ->where('id', '!=', $this->record?->id)
                ->update(['is_default' => false]);
            }
          }),
      ]);
  }

  public static function table(Tables\Table $table): Tables\Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('code')
          ->searchable(),

        Tables\Columns\TextColumn::make('name')
          ->searchable(),

        Tables\Columns\IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),

        Tables\Columns\IconColumn::make('is_default')
          ->label('Default')
          ->boolean(),

        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
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
      'index' => Pages\ListCountries::route('/'),
      'create' => Pages\CreateCountry::route('/create'),
      'edit' => Pages\EditCountry::route('/{record}/edit'),
    ];
  }
}
