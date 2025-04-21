<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillboardResource\Pages;
use App\Filament\Resources\BillboardResource\RelationManagers;
use App\Models\Billboard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillboardResource extends Resource
{
  protected static ?string $model = Billboard::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('uuid')
          ->label('UUID')
          ->maxLength(36),
        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255),
        Forms\Components\Select::make('location_id')
          ->relationship('location', 'name')
          ->required(),
        Forms\Components\TextInput::make('size')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('type')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('price')
          ->required()
          ->numeric()
          ->prefix('$'),
        Forms\Components\TextInput::make('status')
          ->required()
          ->maxLength(255),
        Forms\Components\Textarea::make('description')
          ->columnSpanFull(),
        Forms\Components\TextInput::make('latitude')
          ->numeric(),
        Forms\Components\TextInput::make('longitude')
          ->numeric(),
        Forms\Components\DateTimePicker::make('available_from'),
        Forms\Components\DateTimePicker::make('available_until'),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('uuid')
          ->label('UUID')
          ->searchable(),
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextColumn::make('location.name')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('size')
          ->searchable(),
        Tables\Columns\TextColumn::make('type')
          ->searchable(),
        Tables\Columns\TextColumn::make('price')
          ->money()
          ->sortable(),
        Tables\Columns\TextColumn::make('status')
          ->searchable(),
        Tables\Columns\TextColumn::make('latitude')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('longitude')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('available_from')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('available_until')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
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
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListBillboards::route('/'),
      'create' => Pages\CreateBillboard::route('/create'),
      'edit' => Pages\EditBillboard::route('/{record}/edit'),
    ];
  }
}
