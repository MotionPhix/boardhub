<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Filament\Resources\LocationResource\RelationManagers\BillboardsRelationManager;
use App\Models\Location;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Form;
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

  protected static ?int $navigationSort = 2;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Basic Information')
              ->schema([
                Forms\Components\TextInput::make('name')
                  ->required()
                  ->maxLength(255)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                    if ($operation === 'create') {
                      $set('slug', Str::slug($state));
                    }
                  }),

                Forms\Components\TextInput::make('slug')
                  ->required()
                  ->maxLength(255)
                  ->unique(ignoreRecord: true)
                  ->disabled()
                  ->dehydrated(),

                Forms\Components\Toggle::make('is_active')
                  ->label('Active')
                  ->default(true)
                  ->helperText('Inactive locations will not be available for new billboards'),

                Forms\Components\MarkdownEditor::make('description')
                  ->columnSpanFull(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Address Details')
              ->schema([
                Forms\Components\TextInput::make('city')
                  ->required()
                  ->maxLength(255),

                Forms\Components\TextInput::make('state')
                  ->required()
                  ->maxLength(255),

                Forms\Components\Select::make('country')
                  ->required()
                  ->searchable()
                  ->options(self::getCountryOptions()),

                Forms\Components\TextInput::make('postal_code')
                  ->required()
                  ->maxLength(20),
              ])
              ->columns(2),

            Forms\Components\Section::make('Geographic Coordinates')
              ->schema([
                Map::make('location')
                  ->label('Location')
                  ->columnSpanFull()
                  // Basic Configuration
                  ->defaultLocation(latitude: -13.9626, longitude: 33.7741)
                  ->draggable(true)
                  ->clickable(true)
                  ->zoom(13)
                  ->minZoom(0)
                  ->maxZoom(18)
                  ->detectRetina(true)

                  // Marker Configuration
                  ->showMarker(true)
                  ->markerColor('#3b82f6')

                  // Controls
                  ->showFullscreenControl(true)
                  ->showZoomControl(true)

                  // Location Features
                  ->showMyLocationButton(true)

                  // State Management
                  ->afterStateUpdated(function (Forms\Set $set, ?array $state): void {
                    if ($state) {
                      $set('latitude', $state['lat']);
                      $set('longitude', $state['lng']);
                    }
                  })
                  ->afterStateHydrated(function ($state, $record, Forms\Set $set): void {
                    if ($record) {
                      $set('location', [
                        'lat' => $record->latitude,
                        'lng' => $record->longitude,
                      ]);
                    }
                  }),

                Forms\Components\Grid::make()
                  ->schema([
                    Forms\Components\TextInput::make('latitude')
                      ->required()
                      ->numeric()
                      ->step(0.000001)
                      ->minValue(-90)
                      ->maxValue(90)
                      ->default(-13.9626)
                      ->reactive(),

                    Forms\Components\TextInput::make('longitude')
                      ->required()
                      ->numeric()
                      ->step(0.000001)
                      ->minValue(-180)
                      ->maxValue(180)
                      ->default(33.7741)
                      ->reactive(),
                  ])
                  ->columns(2),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Status')
              ->schema([
                Forms\Components\Placeholder::make('billboards_count')
                  ->label('Total Billboards')
                  ->content(function (?Location $record): string {
                    if (!$record) return '0';
                    return (string)$record->billboards()->count();
                  }),

                Forms\Components\Placeholder::make('active_billboards')
                  ->label('Active Billboards')
                  ->content(function (?Location $record): string {
                    if (!$record) return '0';
                    return (string)$record->billboards()
                      ->whereHas('contracts', fn($query) => $query->where('billboard_contract.booking_status', 'in_use')
                      )->count();
                  }),
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
          ->sortable(),
        Tables\Columns\TextColumn::make('city')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('state')
          ->searchable(),
        Tables\Columns\TextColumn::make('country')
          ->searchable(),
        Tables\Columns\TextColumn::make('postal_code')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards')
          ->sortable(),
        Tables\Columns\TextColumn::make('active_billboards')
          ->label('Active')
          ->counts('billboards', function ($query) {
            $query->whereHas('contracts', function ($query) {
              $query->where('billboard_contract.booking_status', 'in_use');
            });
          })
          ->color(fn(string $state): string => $state > 0 ? 'success' : 'gray')
          ->sortable(),
        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->options(self::getCountryOptions())
          ->searchable(),
        Tables\Filters\Filter::make('has_billboards')
          ->label('Has Billboards')
          ->query(fn(Builder $query): Builder => $query->has('billboards')),
        Tables\Filters\Filter::make('has_active_billboards')
          ->label('Has Active Billboards')
          ->query(fn(Builder $query): Builder => $query->whereHas('billboards', function ($query) {
            $query->whereHas('contracts', function ($query) {
              $query->where('billboard_contract.booking_status', 'in_use');
            });
          })),
        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active Status')
          ->nullable(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('map')
          ->icon('heroicon-o-map')
          ->url(fn(Location $record): string => "https://www.openstreetmap.org/?mlat={$record->latitude}&mlon={$record->longitude}&zoom=15")
          ->openUrlInNewTab(),
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

  private static function getCountryOptions(): array
  {
    return [
      'MW' => 'Malawi',
      'ZW' => 'Zambia'
      // Add more countries as needed
    ];
  }
}
