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
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BillboardResource extends Resource
{
  protected static ?string $model = Billboard::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    Forms\Components\TextInput::make('city')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('state')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('country')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('postal_code')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\Grid::make(2)
                      ->schema([
                        Forms\Components\TextInput::make('latitude')
                          ->numeric()
                          ->required(),
                        Forms\Components\TextInput::make('longitude')
                          ->numeric()
                          ->required(),
                      ]),
                  ]),

                Forms\Components\Select::make('type')
                  ->options([
                    'static' => 'Static',
                    'digital' => 'Digital',
                    'mobile' => 'Mobile',
                  ])
                  ->required(),

                Forms\Components\TextInput::make('size')
                  ->required()
                  ->helperText('Format: width x height (e.g., 48 x 14)'),

                Forms\Components\Grid::make()
                  ->schema([
                    Forms\Components\TextInput::make('base_price')
                      ->label('Base Price')
                      ->numeric()
                      ->prefix(fn() => Settings::get('default_currency.symbol', 'MK'))
                      ->required(),

                    Forms\Components\Select::make('currency_code')
                      ->label('Currency')
                      ->options(collect(Settings::getAvailableCurrencies())->pluck('name', 'code'))
                      ->default(Settings::get('default_currency.code', 'MWK'))
                      ->required(),
                  ])
                  ->columns(2),

                Forms\Components\Select::make('physical_status')
                  ->options(Billboard::getPhysicalStatuses())
                  ->required()
                  ->default(Billboard::PHYSICAL_STATUS_OPERATIONAL)
                  ->helperText('The current physical condition of the billboard'),
              ])
              ->columns(2),

            Forms\Components\Section::make('Location Details')
              ->schema([
                Forms\Components\Grid::make(2)
                  ->schema([
                    Forms\Components\TextInput::make('latitude')
                      ->numeric()
                      ->helperText('Specific billboard location if different from general location'),
                    Forms\Components\TextInput::make('longitude')
                      ->numeric(),
                  ]),
                Forms\Components\Textarea::make('description')
                  ->rows(3)
                  ->columnSpanFull(),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Images')
              ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                  ->collection('billboard-images')
                  ->multiple()
                  ->maxFiles(5)
                  ->image()
                  ->imageEditor()
                  ->columnSpanFull()
                  ->helperText('Upload up to 5 images. First image will be used as the main image.'),
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
        Tables\Columns\SpatieMediaLibraryImageColumn::make('main-image')
          ->collection('billboard-images')
          ->conversion('thumb')
          ->circular(false)
          ->label('Image'),

        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('location.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('type')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'static' => 'gray',
            'digital' => 'success',
            'mobile' => 'warning',
          }),

        Tables\Columns\TextColumn::make('size')
          ->searchable(),

        Tables\Columns\TextColumn::make('base_price')
          ->label('Base Price')
          ->money(fn(Model $record): string => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('physical_status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            Billboard::PHYSICAL_STATUS_OPERATIONAL => 'success',
            Billboard::PHYSICAL_STATUS_MAINTENANCE => 'warning',
            Billboard::PHYSICAL_STATUS_DAMAGED => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn(string $state) => Billboard::getPhysicalStatuses()[$state]),

        Tables\Columns\TextColumn::make('availability_status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'available' => 'success',
            'occupied' => 'warning',
            default => 'danger',
          }),

        Tables\Columns\TextColumn::make('contracts_count')
          ->counts('contracts')
          ->label('Contracts'),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('location')
          ->relationship('location', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\SelectFilter::make('type')
          ->options([
            'static' => 'Static',
            'digital' => 'Digital',
            'mobile' => 'Mobile',
          ]),

        Tables\Filters\SelectFilter::make('physical_status')
          ->options(Billboard::getPhysicalStatuses()),

        Tables\Filters\Filter::make('available')
          ->query(fn(Builder $query): Builder => $query
            ->where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)
            ->whereDoesntHave('contracts', function ($query) {
              $query->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now());
            }))
          ->label('Show Available Only')
          ->toggle(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Physical Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('physical_status')
                ->label('New Physical Status')
                ->options(Billboard::getPhysicalStatuses())
                ->required(),
            ])
            ->action(function (array $data, Collection $records) {
              $records->each->update(['physical_status' => $data['physical_status']]);
            }),
        ]),
      ]);
  }

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Billboard Information')
          ->schema([
            Infolists\Components\Split::make([
              Infolists\Components\Grid::make(2)
                ->schema([
                  Infolists\Components\TextEntry::make('name')
                    ->weight(FontWeight::Bold),

                  Infolists\Components\TextEntry::make('location.name')
                    ->label('Location'),

                  Infolists\Components\TextEntry::make('type')
                    ->badge(),

                  Infolists\Components\TextEntry::make('size'),

                  Infolists\Components\TextEntry::make('base_price')
                    ->label('Base Price')
                    ->money(fn(Model $record): string => $record->currency_code),

                  Infolists\Components\TextEntry::make('physical_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                      Billboard::PHYSICAL_STATUS_OPERATIONAL => 'success',
                      Billboard::PHYSICAL_STATUS_MAINTENANCE => 'warning',
                      Billboard::PHYSICAL_STATUS_DAMAGED => 'danger',
                      default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => Billboard::getPhysicalStatuses()[$state]),

                  Infolists\Components\TextEntry::make('availability_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                      'available' => 'success',
                      'occupied' => 'warning',
                      default => 'danger',
                    }),

                  Infolists\Components\TextEntry::make('current_contract.final_price')
                    ->label('Current Contract Price')
                    ->money(fn(Model $record): string => $record->currency_code)
                    ->visible(fn($record) => $record->current_contract !== null),
                ]),

              Infolists\Components\SpatieMediaLibraryImageEntry::make('images')
                ->collection('billboard-images')
                ->conversion('thumb')
                ->label('Images')
                ->columns(2),

            ])->from('lg'),
          ]),

        Infolists\Components\Section::make('Location Details')
          ->schema([
            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('location.city')
                  ->size(36)
                  ->label('City'),

                Infolists\Components\TextEntry::make('location.state')
                  ->size(36)
                  ->label('State'),

                Infolists\Components\TextEntry::make('location.country')
                  ->size(36)
                  ->label('Country'),
              ]),

            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('latitude')
                  ->size(36),

                Infolists\Components\TextEntry::make('longitude')
                  ->size(36),
              ]),

            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('description')
                  ->size(36)
                  ->columnSpan(2)
              ])
          ])
          ->collapsible(),
      ]);
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
    return static::getModel()::where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)
        ->whereDoesntHave('contracts', function ($query) {
          $query->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
        })
        ->count() . ' available';
  }

  protected function shouldPersistTableFiltersInSession(): bool
  {
    return true;
  }

  protected function getTableRecordUrlUsing(): ?\Closure
  {
    return fn(Model $record): string => auth()->user()->can('view_billboard')
      ? static::getUrl('view', ['record' => $record])
      : '';
  }
}
