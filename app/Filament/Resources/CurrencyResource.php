<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
  protected static ?string $model = Currency::class;

  protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
  protected static ?string $navigationGroup = 'System';
  protected static ?int $navigationSort = 101;

  public static function form(Form $form): Form
  {
    return $form->schema([
      Forms\Components\TextInput::make('code')
        ->required()
        ->maxLength(3)
        ->alpha()
        ->unique(ignoreRecord: true),

      Forms\Components\TextInput::make('symbol')
        ->required()
        ->maxLength(5),

      Forms\Components\TextInput::make('name')
        ->required()
        ->maxLength(255),

      Forms\Components\Toggle::make('is_default')
        ->label('Set as Default'),
    ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('code')
          ->searchable(),

        Tables\Columns\TextColumn::make('symbol')
          ->searchable(),

        Tables\Columns\TextColumn::make('name')
          ->searchable(),

        Tables\Columns\IconColumn::make('is_default')
          ->boolean(),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->disabled(fn (Currency $record): bool => $record->is_default),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCurrencies::route('/'),
      'create' => Pages\CreateCurrency::route('/create'),
      'edit' => Pages\EditCurrency::route('/{record}/edit'),
    ];
  }
}
