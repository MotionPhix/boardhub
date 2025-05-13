<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillboardResource\Pages;
use App\Filament\Resources\BillboardResource\RelationManagers\ContractsRelationManager;
use App\Models\Billboard;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Settings;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillboardResource extends Resource
{
  protected static ?string $model = Billboard::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 3;

  public static function form(Form $form): Form
  {
    $currency = Settings::getDefaultCurrency();

    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Basic Information')
              ->schema([
                Forms\Components\TextInput::make('name')
                  ->label('Site')
                  ->required()
                  ->maxLength(255)
                  ->columnSpanFull()
                  ->placeholder('This defines the exact placement of the billboard')
                  ->live(onBlur: true),

                Forms\Components\TextInput::make('code')
                  ->label('Billboard Code')
                  ->disabled()
                  ->helperText('This code will be automatically generated after saving')
                  ->dehydrated(false) // Since it's handled by the model
                  ->columnSpanFull()
                  ->formatStateUsing(function ($state) {
                    if (!$state) {
                      return '[Auto-generated]';
                    }
                    return $state;
                  }),

                /*Forms\Components\Select::make('location_id')
                  ->relationship(
                    'location',
                    'name',
                    fn ($query) => $query->with(['city', 'state', 'country'])
                  )
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionForm([
                    Forms\Components\Select::make('country_code')
                      ->label('Country')
                      ->options(Country::query()
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->afterStateUpdated(fn (callable $set) => $set('state_code', null)),

                    Forms\Components\Select::make('state_code')
                      ->label('State/Region')
                      ->options(fn (callable $get): Collection => State::query()
                        ->where('country_code', $get('country_code'))
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->visible(fn (callable $get) => filled($get('country_code')))
                      ->afterStateUpdated(fn (callable $set) => $set('city_code', null)),

                    Forms\Components\Select::make('city_code')
                      ->label('City')
                      ->options(fn (callable $get): Collection => City::query()
                        ->where('state_code', $get('state_code'))
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->visible(fn (callable $get) => filled($get('state_code'))),

                    Forms\Components\TextInput::make('name')
                      ->label('Area/Township')
                      ->placeholder('Area or part of the city, e.g. Area 25')
                      ->helperText('Specific area, neighborhood, or landmark within the city')
                      ->required()
                      ->maxLength(255),

                    Forms\Components\MarkdownEditor::make('description')
                      ->label('Description')
                      ->placeholder('Additional details about this location')
                      ->helperText('Include any relevant information about accessibility, surroundings, or special characteristics')
                      ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                      ->label('Active')
                      ->helperText('Inactive locations will not be available for billboard placement')
                      ->default(true),
                  ])
                  ->createOptionAction(function (Action $action) {
                    return $action
                      ->modalWidth('lg')
                      ->modalHeading('Add New Location')
                      ->modalDescription('Create a new location for billboard placement')
                      ->modalIcon('heroicon-o-map-pin')
                      ->closeModalByClickingAway(false);
                  })
                  ->createOptionUsing(function (array $data, Forms\Set $set): Model {
                    $location = Location::create([
                      ...array_filter($data), // Remove any null values
                      'code' => Location::generateLocationCode($data['city_code']),
                    ]);

                    // Ensure the new location is selected
                    $set('location_id', $location->id);

                    return $location;
                  })
                  ->getSearchResultsUsing(function (string $search): array {
                    return Location::query()
                      ->where('is_active', true)
                      ->where('name', 'like', "%{$search}%")
                      ->with(['city', 'state', 'country'])
                      ->limit(50)
                      ->get()
                      ->map(function ($location) {
                        return [
                          'id' => $location->id,
                          'name' => $location->name . ' - ' . $location->city?->name,
                        ];
                      })
                      ->toArray();
                  })
                  ->getOptionLabelUsing(fn ($value): ?string => Location::find($value)?->name)
                  ->live(),*/

                Forms\Components\Select::make('location_id')
                  ->relationship('location', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set) {
                    // Clear dependent fields if location is changed/cleared
                    if (blank($state)) {
                      $set('code', null);
                      return;
                    }
                  })
                  ->createOptionForm([
                    Forms\Components\Select::make('country_code')
                      ->label('Country')
                      ->options(Country::query()
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->afterStateUpdated(fn (Forms\Set $set) => $set('state_code', null)),

                    Forms\Components\Select::make('state_code')
                      ->label('State/Region')
                      ->options(fn (Forms\Get $get): Collection => State::query()
                        ->where('country_code', $get('country_code'))
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->visible(fn (Forms\Get $get) => filled($get('country_code')))
                      ->afterStateUpdated(fn (Forms\Set $set) => $set('city_code', null)),

                    Forms\Components\Select::make('city_code')
                      ->label('City')
                      ->options(fn (Forms\Get $get): Collection => City::query()
                        ->where('state_code', $get('state_code'))
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->required()
                      ->searchable()
                      ->preload()
                      ->live()
                      ->visible(fn (Forms\Get $get) => filled($get('state_code'))),

                    Forms\Components\TextInput::make('name')
                      ->label('Area/Township')
                      ->placeholder('Area or part of the city, e.g. Area 25')
                      ->helperText('Specific area, neighborhood, or landmark within the city')
                      ->required()
                      ->maxLength(255),

                    Forms\Components\MarkdownEditor::make('description')
                      ->label('Description')
                      ->placeholder('Additional details about this location')
                      ->helperText('Include any relevant information about accessibility, surroundings, or special characteristics')
                      ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                      ->label('Active')
                      ->helperText('Inactive locations will not be available for billboard placement')
                      ->default(true),
                  ])
                  ->createOptionAction(function (Action $action) {
                    return $action
                      ->modalWidth('lg')
                      ->modalHeading('Add New Location')
                      ->modalDescription('Create a new location for billboard placement')
                      ->modalIcon('heroicon-o-map-pin')
                      ->closeModalByClickingAway(false);
                  })
                  ->createOptionUsing(function (array $data, Forms\Set $set): Model {
                    return DB::transaction(function () use ($data, $set) {
                      $location = Location::create([
                        ...array_filter($data),
                        'code' => Location::generateLocationCode($data['city_code']),
                      ]);

                      // Set the newly created location ID in the form state
                      $set('location_id', $location->id);

                      return $location;
                    });
                  })
                  ->optionsLimit(15)
                  ->prefixIcon('heroicon-m-map-pin')
                  ->helperText('Select an existing location or create a new one'),

                Forms\Components\TextInput::make('size')
                  ->placeholder('The format should be Wm x Hm')
                  ->maxLength(255),
              ])
              ->columns(2),

            Forms\Components\Section::make('Billboard Map Coordinates')
              ->schema([
                Forms\Components\Grid::make(2)
                  ->schema([
                    Forms\Components\TextInput::make('latitude')
                      ->label('Latitude')
                      ->numeric()
                      ->rules(['nullable', 'numeric', 'between:-90,90'])
                      ->placeholder('-13.962476'),

                    Forms\Components\TextInput::make('longitude')
                      ->label('Longitude')
                      ->numeric()
                      ->rules(['nullable', 'numeric', 'between:-180,180'])
                      ->placeholder('33.774827'),
                  ]),
              ]),

            Forms\Components\Section::make('Pricing')
              ->schema([
                Forms\Components\TextInput::make('base_price')
                  ->label('Monthly Fee')
                  ->numeric()
                  ->rules(['required', 'numeric', 'min:0'])
                  ->prefix($currency['symbol'])
                  ->default(0),

                Forms\Components\Select::make('currency_code')
                  ->label('Currency')
                  ->options(function (): array {
                    return collect(Settings::getAvailableCurrencies())
                      ->mapWithKeys(fn(array $currency) => [
                        $currency['code'] => "{$currency['name']} ({$currency['code']})"
                      ])
                      ->toArray();
                  })
                  ->default(fn() => Settings::getDefaultCurrency()['code'])
                  ->required(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Status')
              ->schema([
                Forms\Components\Toggle::make('is_active')
                  ->label('Active')
                  ->default(true)
                  ->inline(),

                Forms\Components\Select::make('physical_status')
                  ->label('Physical Status')
                  ->options(Billboard::getPhysicalStatuses())
                  ->required()
                  ->default(Billboard::PHYSICAL_STATUS_OPERATIONAL)
                  ->helperText('Current physical condition of the billboard')
                  ->selectablePlaceholder(false)
                  ->native(false) // This enables custom styling
                  ->formatStateUsing(function (?string $state): string {
                    return Billboard::getPhysicalStatuses()[$state] ?? $state;
                  })
                  ->columnSpan(2)
              ])
              ->columns(3),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Media')
              ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('billboard_images')
                  ->label('Billboard Images')
                  ->collection('billboard_images')
                  ->multiple()
                  ->maxFiles(5)
                  ->image()
                  ->imageEditor()
                  ->columnSpanFull(),
              ])
              ->collapsible()

            /*Forms\Components\Section::make('Current Revenue')
              ->schema([
                Forms\Components\Placeholder::make('current_revenue')
                  ->label('Current Monthly Revenue')
                  ->content(function (?Billboard $record) use ($currency) {
                    if (!$record) return $currency['symbol'] . '0.00';

                    return $currency['symbol'] . number_format($record->contracts()
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now())
                        ->sum('contract_final_amount'), 2);
                  }),

                Forms\Components\Placeholder::make('active_contracts')
                  ->label('Active Contracts')
                  ->content(function (?Billboard $record) {
                    if (!$record) return 0;

                    return $record->contracts()
                      ->whereDate('start_date', '<=', now())
                      ->whereDate('end_date', '>=', now())
                      ->count();
                  }),
              ])
              ->collapsible(),*/
          ])
          ->columnSpan(['lg' => 1]),
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\SpatieMediaLibraryImageColumn::make('billboard_images')
          ->label('Image')
          ->collection('billboard_images')
          ->square()
          ->stacked()
          ->height(50)
          ->limit(2)
          ->limitedRemainingText(size: 'md'),

        Tables\Columns\TextColumn::make('size')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('code')
          ->searchable()
          ->sortable()
          ->toggleable(),

        Tables\Columns\TextColumn::make('location.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('base_price')
          ->label('Booking Fee')
          ->money(fn($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->color(fn(string $state): string => match (strtolower($state)) {
            Billboard::PHYSICAL_STATUS_OPERATIONAL => 'success',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'warning',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn(string $state): string => Billboard::getPhysicalStatuses()[strtolower($state)])
          ->sortable(),

        /*Tables\Columns\TextColumn::make('current_revenue')
          ->money(fn($record) => $record->currency_code)
          ->state(function (Billboard $record): float {
            return $record->contracts()
              ->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now())
              ->sum('contract_final_amount');
          })
          ->sortable(),*/

        Tables\Columns\IconColumn::make('is_active')
          ->label('Active')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('contracts_count')
          ->counts('contracts')
          ->label('Contracts')
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\TernaryFilter::make('is_active')
          ->label('Active')
          ->boolean()
          ->trueLabel('Active billboards')
          ->falseLabel('Inactive billboards')
          ->placeholder('All billboards'),

        Tables\Filters\SelectFilter::make('physical_status')
          ->options(Billboard::getPhysicalStatuses())
          ->label('Physical Status'),

        Tables\Filters\SelectFilter::make('location')
          ->relationship('location', 'name'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }

  public static function getRelations(): array
  {
    return [
      ContractsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListBillboards::route('/'),
      'create' => Pages\CreateBillboard::route('/create'),
      'view' => Pages\ViewBillboard::route('/{record}'),
      'edit' => Pages\EditBillboard::route('/{record}/edit'),
    ];
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }
}
