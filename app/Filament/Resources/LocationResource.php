<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Filament\Resources\LocationResource\RelationManagers\BillboardsRelationManager;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
  protected static ?string $model = Location::class;

  protected static ?string $navigationIcon = 'heroicon-o-map-pin';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 2;

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
                    if ($operation !== 'create') {
                      return;
                    }

                    $set('slug', str()->slug($state));
                  }),

                Forms\Components\TextInput::make('slug')
                  ->disabled()
                  ->dehydrated()
                  ->unique(Location::class, 'slug', ignoreRecord: true),

                Forms\Components\MarkdownEditor::make('description')
                  ->columnSpanFull()
                  ->maxLength(65535),
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
                  ->options(config('countries')), // You'll need to create this config

                Forms\Components\TextInput::make('postal_code')
                  ->required()
                  ->maxLength(20),
              ])
              ->columns(2),

            /*Forms\Components\Section::make('Coordinates')
              ->schema([
                Forms\Components\TextInput::make('latitude')
                  ->required()
                  ->numeric()
                  ->minValue(-90)
                  ->maxValue(90)
                  ->step(0.000001),

                Forms\Components\TextInput::make('longitude')
                  ->required()
                  ->numeric()
                  ->minValue(-180)
                  ->maxValue(180)
                  ->step(0.000001),
              ])
              ->columns(2),
          ])
          ->columnSpan(['lg' => 2]),*/

        Forms\Components\Section::make('Geographic Coordinates')
          ->schema([
            Forms\Components\Grid::make()
              ->schema([
                Forms\Components\TextInput::make('latitude')
                  ->required()
                  ->numeric()
                  ->minValue(-90)
                  ->maxValue(90)
                  ->step(0.000001)
                  ->prefix('Lat:')
                  ->inputMode('decimal'),
                Forms\Components\TextInput::make('longitude')
                  ->required()
                  ->numeric()
                  ->minValue(-180)
                  ->maxValue(180)
                  ->step(0.000001)
                  ->prefix('Lng:')
                  ->inputMode('decimal'),
              ])
              ->columns(2),
            Forms\Components\View::make('filament.forms.components.map')
              ->columnSpanFull(),
          ]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Status')
              ->schema([
                Forms\Components\Toggle::make('is_active')
                  ->label('Active')
                  ->helperText('Inactive locations will not be available for new billboards')
                  ->default(true),

                Forms\Components\Placeholder::make('created_at')
                  ->label('Created')
                  ->content(fn (?Location $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Forms\Components\Placeholder::make('updated_at')
                  ->label('Last modified')
                  ->content(fn (?Location $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
              ]),

            Forms\Components\Section::make('Statistics')
              ->schema([
                Forms\Components\Placeholder::make('billboards_count')
                  ->label('Total Billboards')
                  ->content(fn (?Location $record): string => $record?->billboards_count ?? '0'),

                Forms\Components\Placeholder::make('active_billboards_count')
                  ->label('Active Billboards')
                  ->content(fn (?Location $record): string => $record?->active_billboards_count ?? '0'),
              ]),
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
          ->color(fn (string $state): string => $state > 0 ? 'success' : 'gray')
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->options(self::getCountryOptions())
          ->searchable(),
        Tables\Filters\Filter::make('has_billboards')
          ->label('Has Billboards')
          ->query(fn (Builder $query): Builder =>
          $query->has('billboards')),
        Tables\Filters\Filter::make('has_active_billboards')
          ->label('Has Active Billboards')
          ->query(fn (Builder $query): Builder =>
          $query->whereHas('billboards', function ($query) {
            $query->whereHas('contracts', function ($query) {
              $query->where('billboard_contract.booking_status', 'in_use');
            });
          })),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('map')
          ->icon('heroicon-o-map')
          ->url(fn (Location $record): string =>
          "https://www.openstreetmap.org/?mlat={$record->latitude}&mlon={$record->longitude}&zoom=15")
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
      // Add more countries as needed
    ];
  }
}
