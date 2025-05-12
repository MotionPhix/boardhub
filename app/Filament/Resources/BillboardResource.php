<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillboardResource\Pages;
use App\Filament\Resources\BillboardResource\RelationManagers\ContractsRelationManager;
use App\Models\Billboard;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                  ->required()
                  ->maxLength(255)
                  ->live(onBlur: true)
                  ->afterStateUpdated(fn (string $state, Forms\Set $set) =>
                  $set('code', str()->snake($state))),

                Forms\Components\TextInput::make('code')
                  ->disabled()
                  ->dehydrated()
                  ->required()
                  ->unique(ignoreRecord: true)
                  ->maxLength(255),

                Forms\Components\Select::make('location_id')
                  ->relationship('location', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('address')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                      ->maxLength(65535)
                      ->columnSpanFull(),
                  ]),

                Forms\Components\TextInput::make('size')
                  ->maxLength(255),
              ])
              ->columns(2),

            Forms\Components\Section::make('Location Details')
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
                  ->label('Base Price')
                  ->numeric()
                  ->rules(['required', 'numeric', 'min:0'])
                  ->prefix($currency['symbol'])
                  ->default(0),

                Forms\Components\Select::make('currency_code')
                  ->options([
                    'MWK' => 'Malawian Kwacha (MWK)',
                    'ZMW' => 'Zambian Kwacha (ZMW)',
                    'ZWL' => 'Zimbabwean Dollar (ZWL)',
                    'USD' => 'US Dollar (USD)',
                  ])
                  ->default('MWK')
                  ->required(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Status')
              ->schema([
                Forms\Components\Toggle::make('is_active')
                  ->label('Active')
                  ->default(true)
                  ->inline(false),

                Forms\Components\Select::make('physical_status')
                  ->label('Physical Status')
                  ->options(Billboard::getPhysicalStatuses())
                  ->required()
                  ->default(Billboard::PHYSICAL_STATUS_OPERATIONAL)
                  ->helperText('Current physical condition of the billboard')
                  ->badge()
                  ->colors([
                    'success' => Billboard::PHYSICAL_STATUS_OPERATIONAL,
                    'warning' => Billboard::PHYSICAL_STATUS_MAINTENANCE,
                    'danger' => Billboard::PHYSICAL_STATUS_DAMAGED,
                  ]),
              ])
              ->columns(2),
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
              ->collapsible(),

            Forms\Components\Section::make('Current Revenue')
              ->schema([
                Forms\Components\Placeholder::make('current_revenue')
                  ->label('Current Monthly Revenue')
                  ->content(function (?Billboard $record) use($currency) {
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
          ->circular()
          ->stacked()
          ->limit(3),

        Tables\Columns\TextColumn::make('name')
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
          ->money(fn ($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            Billboard::PHYSICAL_STATUS_OPERATIONAL => 'success',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'warning',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn (string $state): string => Billboard::getPhysicalStatuses()[$state])
          ->sortable(),

        Tables\Columns\TextColumn::make('current_revenue')
          ->money(fn ($record) => $record->currency_code)
          ->state(function (Billboard $record): float {
            return $record->contracts()
              ->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now())
              ->sum('contract_final_amount');
          })
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
}
