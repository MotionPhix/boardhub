<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Filament\Resources\ClientResource\RelationManagers\ContractsRelationManager;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
  protected static ?string $model = Client::class;

  protected static ?string $navigationIcon = 'heroicon-o-users';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 2;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Tabs::make('Client Management')
          ->tabs([
            Forms\Components\Tabs\Tab::make('Basic Information')
              ->schema([
                Forms\Components\TextInput::make('name')
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('email')
                  ->email()
                  ->required()
                  ->maxLength(255)
                  ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                  ->tel()
                  ->maxLength(255),
                Forms\Components\TextInput::make('company')
                  ->maxLength(255),
                Forms\Components\Textarea::make('address')
                  ->maxLength(65535)
                  ->columnSpanFull(),
              ])
              ->columns(2),

            Forms\Components\Tabs\Tab::make('Documents')
              ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('client_documents')
                  ->collection('client_documents')
                  ->multiple()
                  ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                  ->maxSize(5120)
                  ->downloadable()
                  ->columnSpanFull(),
              ]),
          ])
          ->columnSpanFull(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('email')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('phone')
          ->searchable(),
        Tables\Columns\TextColumn::make('company')
          ->searchable(),
        Tables\Columns\TextColumn::make('active_contracts_count')
          ->label('Active Contracts')
          ->counts('contracts', fn (Builder $query) => $query
            ->where('agreement_status', 'active')
            ->whereHas('billboards', function ($query) {
              $query->wherePivot('booking_status', 'in_use');
            }))
          ->sortable(),
        Tables\Columns\TextColumn::make('total_contracts_value')
          ->label('Total Contract Value')
          ->money()
          ->state(fn ($record) => $record->contracts()
            ->where('agreement_status', 'active')
            ->sum('total_amount'))
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\Filter::make('active_contracts')
          ->query(fn (Builder $query): Builder => $query
            ->whereHas('contracts', function ($query) {
              $query->where('agreement_status', 'active')
                ->whereHas('billboards', function ($query) {
                  $query->wherePivot('booking_status', 'in_use');
                });
            }))
          ->label('Has Active Contracts')
          ->toggle(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
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
      ContractsRelationManager::make(),
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListClients::route('/'),
      'create' => Pages\CreateClient::route('/create'),
      'view' => Pages\ViewClient::route('/{record}'),
      'edit' => Pages\EditClient::route('/{record}/edit'),
    ];
  }

  public static function getGloballySearchableAttributes(): array
  {
    return ['name', 'email', 'company', 'phone'];
  }
}
