<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers\ContractsRelationManager;
use App\Models\Client;
use Cheesegrits\FilamentPhoneNumbers;
use Brick\PhoneNumber\PhoneNumberFormat;
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

  protected static ?int $navigationSort = 4;

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

                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('phone')
                  ->displayFormat(PhoneNumberFormat::INTERNATIONAL)
                  ->databaseFormat(PhoneNumberFormat::INTERNATIONAL)
                  ->mask('+999 999 999 999 999'),

                Forms\Components\TextInput::make('company')
                  ->maxLength(255),

                Forms\Components\TextInput::make('street')
                  ->maxLength(65535),

                Forms\Components\TextInput::make('city')
                  ->maxLength(65535),

                Forms\Components\TextInput::make('state')
                  ->maxLength(65535),

                Forms\Components\TextInput::make('country')
                  ->maxLength(65535)
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

        FilamentPhoneNumbers\Columns\PhoneNumberColumn::make('phone')
          ->searchable(),

        Tables\Columns\TextColumn::make('company')
          ->searchable(),

        Tables\Columns\TextColumn::make('active_contracts_count')
          ->label('Active Contracts')
          ->counts('contracts', fn (Builder $query) => $query
            ->where('agreement_status', 'active')
            ->where('end_date', '>=', now())
            ->whereHas('billboards', function ($query) {
              $query->wherePivot('booking_status', 'in_use');
            }))
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
                ->where('end_date', '>=', now())
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
