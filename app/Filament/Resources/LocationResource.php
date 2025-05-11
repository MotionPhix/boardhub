<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers\BillboardsRelationManager;
use App\Models\Location;
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

  protected static ?int $navigationSort = 3;

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

                Forms\Components\Toggle::make('is_active')
                  ->label('Council Cleared')
                  ->default(true)
                  ->helperText('Uncleared locations will not be available for new billboards'),

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
              ])
              ->columns(2),
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
                      ->whereHas('contracts', fn($query) => $query
                        ->where('billboard_contract.booking_status', 'in_use'))
                      ->count();
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
          ->sortable()
          ->description(fn (Location $record): string => $record->full_address),

        Tables\Columns\TextColumn::make('city')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('state')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('country')
          ->searchable(),

        Tables\Columns\TextColumn::make('postal_code')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean()
          ->label('Active')
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('country')
          ->options(self::getCountryOptions())
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

  private static function getCountryOptions(): array
  {
    return [
      'MW' => 'Malawi',
      'ZM' => 'Zambia',
      'ZW' => 'Zimbabwe'
    ];
  }
}
