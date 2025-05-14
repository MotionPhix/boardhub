<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers\BillboardsRelationManager;
use App\Models\Location;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LocationResource extends Resource
{
  protected static ?string $model = Location::class;

  protected static ?string $navigationIcon = 'heroicon-o-map-pin';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 3;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Location Status')
              ->schema([
                Forms\Components\Toggle::make('is_active')
                  ->label('Council Cleared')
                  ->default(true)
                  ->columnSpanFull()
                  ->helperText('Uncleared locations will not be available for new billboards'),

                /*Forms\Components\MarkdownEditor::make('description')
                  ->columnSpanFull(),*/
              ])
              ->columns(2),

            Forms\Components\Section::make('Location Details')
              ->schema([
                Forms\Components\Select::make('country_code')
                  ->label('Country')
                  ->options(fn () => Country::where('is_active', true)
                    ->pluck('name', 'code'))
                  ->required()
                  ->searchable()
                  ->live()
                  ->afterStateUpdated(fn (Set $set) => $set('state_code', null)),

                Forms\Components\Select::make('state_code')
                  ->label('State/Region')
                  ->options(fn (Get $get) => State::where('country_code', $get('country_code'))
                    ->where('is_active', true)
                    ->pluck('name', 'code'))
                  ->required()
                  ->searchable()
                  ->live()
                  ->visible(fn (Get $get) => filled($get('country_code')))
                  ->afterStateUpdated(fn (Set $set) => $set('city_code', null)),

                Forms\Components\Select::make('city_code')
                  ->label('City')
                  ->options(fn (Get $get) => City::where('state_code', $get('state_code'))
                    ->where('is_active', true)
                    ->pluck('name', 'code'))
                  ->required()
                  ->searchable()
                  ->visible(fn (Get $get) => filled($get('state_code'))),

                Forms\Components\TextInput::make('name')
                  ->required()
                  ->label('Area/Township')
                  ->maxLength(255)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                    if ($operation === 'create') {
                      $set('slug', Str::slug($state));
                    }
                  }),
              ])
              ->columns(2),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Quick Stats')
              ->schema([
                Forms\Components\Placeholder::make('billboards_count')
                  ->label('Total Billboards')
                  ->content(fn (?Location $record): string
                  => $record ? (string) $record->billboards()->count() : '0'),

                Forms\Components\Placeholder::make('active_billboards')
                  ->label('Active Billboards')
                  ->content(fn (?Location $record): string
                  => $record ? (string) $record->billboards()
                    ->whereHas('contracts', fn($query) => $query
                      ->where('billboard_contract.booking_status', 'in_use'))
                    ->count() : '0'),
              ])
              ->hidden(fn(?Location $record) => !$record)
              ->visibleOn(['edit', 'view']),
          ])
          ->columnSpan(['lg' => 1]),
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable()
          ->description(fn (Location $record): string => $record->full_address),

        Tables\Columns\TextColumn::make('city.name')
          ->label('City')
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

        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->label('Active')
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country_code')
          ->label('Country')
          ->options(fn () => Country::where('is_active', true)
            ->pluck('name', 'code'))
          ->searchable(),

        Tables\Filters\Filter::make('has_billboards')
          ->label('Has Billboards')
          ->query(fn(Builder $query): Builder => $query->has('billboards')),

        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Status')
          ->trueLabel('Active')
          ->falseLabel('Inactive')
          ->nullable(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }

  public static function getRelations(): array
  {
    return [
      BillboardsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListLocations::route('/'),
      'create' => Pages\CreateLocation::route('/create'),
      'view' => Pages\ViewLocation::route('/{record}'),
      'edit' => Pages\EditLocation::route('/{record}/edit'),
    ];
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }
}
