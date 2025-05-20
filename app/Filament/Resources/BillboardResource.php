<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillboardResource\Pages;
use App\Filament\Resources\BillboardResource\RelationManagers\ContractsRelationManager;
use App\Models\Billboard;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class BillboardResource extends Resource
{
  protected static ?string $model = Billboard::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 3;

  /**
   * @throws \Exception
   */
  public static function form(Form $form): Form
  {
    $defaultCurrency = Currency::getDefault();

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
                  ->dehydrated(false)
                  ->columnSpanFull()
                  ->formatStateUsing(function ($state) {
                    return $state ?: '[Auto-generated]';
                  }),

                Forms\Components\Select::make('location_id')
                  ->relationship('location', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionAction(function (Action $action) {
                    return $action
                      ->modalWidth(MaxWidth::Large)
                      ->stickyModalHeader()
                      ->modalHeading('Add New Location')
                      ->modalDescription('Create a new location for billboard placement')
                      ->modalIcon('heroicon-o-map-pin')
                      ->closeModalByClickingAway(false);
                  })
                  ->createOptionForm([
                    Forms\Components\Select::make('country_code')
                      ->label('Country')
                      ->required()
                      ->preload()
                      ->options(fn () => Country::query()
                        ->where('is_active', true)
                        ->pluck('name', 'code'))
                      ->default(fn () => Country::where('is_default', true)
                        ->first()?->code)
                      ->live(),

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
                      ->afterStateUpdated(fn (Set $set) => $set('city_code', null)),

                    Forms\Components\Select::make('city_code')
                      ->label('City')
                      ->required()
                      ->options(function (Get $get) {
                        $stateCode = $get('state_code');
                        if (!$stateCode) return [];

                        return City::query()
                          ->where('state_code', $stateCode)
                          ->where('is_active', true)
                          ->pluck('name', 'code');
                      })
                      ->searchable()
                      ->preload()
                      ->live()
                      ->visible(fn (Get $get) => filled($get('state_code')))
                      ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                          $city = City::where('code', $state)->first();
                          if ($city) {
                            $set('name', "Enter a township within {$city->name} city");
                          }
                        }
                      }),

                    Forms\Components\TextInput::make('name')
                      ->label('Area/Township')
                      ->placeholder('Area or part of the city, e.g. Area 25')
                      ->helperText('Specific area, neighborhood, or landmark within the city')
                      ->required()
                      ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                      ->label('Active')
                      ->helperText('Inactive locations will not be available for billboard placement')
                      ->default(true),
                  ]),

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
                  ->label('Monthly Rental')
                  ->numeric()
                  ->rules(['required', 'numeric', 'min:0'])
                  ->prefix($defaultCurrency?->symbol ?? 'K')
                  ->default(0),

                Forms\Components\Select::make('currency_code')
                  ->label('Currency')
                  ->options(Currency::query()
                    ->get()
                    ->mapWithKeys(fn (Currency $currency) => [
                      $currency->code => "{$currency->name} ({$currency->code})"
                    ]))
                  ->default(fn () => $defaultCurrency?->code)
                  ->required(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Billboard Status')
              ->schema([
                Forms\Components\Radio::make('is_active')
                  ->label('Is the billboard available for booking')
                  ->boolean()
                  ->inline()
                  ->inlineLabel(false)
                  ->columnSpanFull()
                  ->descriptions([
                    true => 'It is ready for booking.',
                    false => 'It has issues that needs fixing',
                  ])
                  ->disableOptionWhen(fn (Get $get) => $get('physical_status') !== 'operational')
                  ->live(),

                Forms\Components\Select::make('physical_status')
                  ->label('Physical Status')
                  ->options(Billboard::getPhysicalStatuses())
                  ->required()
                  ->default(Billboard::PHYSICAL_STATUS_MAINTENANCE)
                  ->helperText('Current physical condition of the billboard')
                  ->selectablePlaceholder(false)
                  ->native(false)
                  ->formatStateUsing(function (?string $state): string {
                    return Billboard::getPhysicalStatuses()[$state] ?? $state;
                  })
                  ->live()
                  ->afterStateUpdated(
                    fn ($state, Set $set) => $set('is_active', $state === 'operational')
                  )
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
                  ->imageEditorMode(2)
                  ->columnSpanFull(),
              ])
              ->collapsible(),
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
          ->money(fn ($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->color(fn (string $state): string => match (strtolower($state)) {
            Billboard::PHYSICAL_STATUS_OPERATIONAL => 'success',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'warning',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn (string $state): string => Billboard::getPhysicalStatuses()[strtolower($state)])
          ->sortable(),

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
