<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers\LoginActivitiesRelationManager;
use App\Models\Contract;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContractResource extends Resource
{
  protected static ?string $model = Contract::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-text';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 5;

  public static function form(Form $form): Form
  {
    $currency = Settings::getDefaultCurrency();

    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Contract Details')
              ->schema([
                Forms\Components\TextInput::make('contract_number')
                  ->default(fn() => 'CNT-' . date('Y') . '-' . str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT))
                  ->disabled()
                  ->dehydrated()
                  ->required(),

                Forms\Components\Select::make('client_id')
                  ->relationship('client', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                      ->email()
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                      ->tel()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('company')
                      ->maxLength(255),
                  ]),

                Forms\Components\DatePicker::make('start_date')
                  ->required()
                  ->default(now())
                  ->live()
                  ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                  $set('end_date', $state ? date('Y-m-d', strtotime($state . ' +1 month')) : null)
                  ),

                Forms\Components\DatePicker::make('end_date')
                  ->required()
                  ->afterOrEqual(fn (Forms\Get $get) => $get('start_date')),

                Forms\Components\Select::make('agreement_status')
                  ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                  ])
                  ->required()
                  ->default('draft')
                  ->live(),

                Forms\Components\MarkdownEditor::make('notes')
                  ->maxLength(65535)
                  ->columnSpanFull(),
              ])
              ->columns(2),

            Forms\Components\Section::make('Billboard Selection & Pricing')
              ->schema([
                Forms\Components\Select::make('billboards')
                  ->relationship('billboards', 'name')
                  ->multiple()
                  ->preload()
                  ->searchable()
                  ->required()
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (empty($state)) {
                      $set('base_amount', 0);
                      $set('discount_amount', 0);
                      $set('total_amount', 0);
                      return;
                    }

                    $baseAmount = \App\Models\Billboard::whereIn('id', $state)
                      ->sum('base_amount');

                    $set('base_amount', $baseAmount);

                    // Recalculate total with any existing discount
                    $discountAmount = $set('discount_amount') ?? 0;
                    $set('total_amount', $baseAmount - $discountAmount);
                  }),

                Forms\Components\Grid::make(3)
                  ->schema([
                    Forms\Components\TextInput::make('base_amount')
                      ->label('Base Amount')
                      ->numeric()
                      ->prefix($currency['symbol'])
                      ->disabled()
                      ->dehydrated(false),

                    Forms\Components\TextInput::make('discount_amount')
                      ->label('Discount Amount')
                      ->numeric()
                      ->prefix($currency['symbol'])
                      ->default(0)
                      ->live()
                      ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $baseAmount = $get('base_amount') ?? 0;
                        $discountAmount = $state ?? 0;

                        if ($discountAmount > $baseAmount) {
                          $set('discount_amount', $baseAmount);
                          $discountAmount = $baseAmount;
                        }

                        $set('total_amount', $baseAmount - $discountAmount);
                      }),

                    Forms\Components\TextInput::make('total_amount')
                      ->label('Final Amount')
                      ->numeric()
                      ->prefix($currency['symbol'])
                      ->required()
                      ->disabled()
                      ->dehydrated(),
                  ]),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Documents')
              ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('contract_documents')
                  ->collection('contract_documents')
                  ->multiple()
                  ->maxFiles(5)
                  ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                  ->columnSpanFull(),

                Forms\Components\SpatieMediaLibraryFileUpload::make('signed_contract')
                  ->collection('signed_contracts')
                  ->maxFiles(1)
                  ->acceptedFileTypes(['application/pdf'])
                  ->columnSpanFull()
                  ->visible(fn(Forms\Get $get) => $get('agreement_status') === 'active'),
              ])
              ->collapsible(),

            Forms\Components\Section::make('Payment Terms')
              ->schema([
                Forms\Components\Select::make('payment_terms')
                  ->options(function () {
                    $terms = Settings::get('document_settings.default_payment_terms', []);
                    return collect($terms)->pluck('description', 'days');
                  })
                  ->label('Payment Terms'),
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
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->money()
          ->sortable(),
        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->colors([
            'danger' => 'cancelled',
            'warning' => 'draft',
            'success' => 'active',
            'gray' => 'completed',
          ]),
        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards'),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('agreement_status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ]),
        Tables\Filters\SelectFilter::make('booking_status')
          ->options(collect(BookingStatus::cases())->pluck('value', 'value'))
          ->query(function (Builder $query, array $data) {
            if (!$data['value']) return $query;

            return $query->whereHas('billboards', function ($query) use ($data) {
              $query->where('billboard_contract.booking_status', $data['value']);
            });
          }),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('download_contract')
          ->icon('heroicon-o-document-arrow-down')
          ->label('Download')
          ->visible(fn(Contract $record) => $record->hasMedia('signed_contracts'))
          ->action(fn(Contract $record) => redirect($record->getFirstMediaUrl('signed_contracts'))),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('agreement_status')
                ->label('New Agreement Status')
                ->options([
                  'draft' => 'Draft',
                  'active' => 'Active',
                  'completed' => 'Completed',
                  'cancelled' => 'Cancelled',
                ])
                ->required(),
            ])
            ->action(function (array $data, $records) {
              $records->each(function ($record) use ($data) {
                $record->update(['agreement_status' => $data['agreement_status']]);

                // Update booking status for all billboards
                foreach ($record->billboards as $billboard) {
                  $billboard->pivot->update([
                    'booking_status' => $data['agreement_status'] === 'active'
                      ? BookingStatus::IN_USE->value
                      : ($data['agreement_status'] === 'completed'
                        ? BookingStatus::COMPLETED->value
                        : BookingStatus::CANCELLED->value),
                  ]);
                }
              });
            }),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      LoginActivitiesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListContracts::route('/'),
      'create' => Pages\CreateContract::route('/create'),
      'view' => Pages\ViewContract::route('/{record}'),
      'edit' => Pages\EditContract::route('/{record}/edit'),
    ];
  }

  public static function getGlobalSearchAttributes(): array
  {
    return ['contract_number', 'client.name', 'client.company'];
  }

  public static function getGloballySearchableAttributes(): array
  {
    return ['contract_number', 'client.name', 'client.company'];
  }

  public static function getGlobalSearchResultDetails(Model $record): array
  {
    return [
      'Client' => $record->client->name,
      'Amount' => 'MK ' . number_format($record->total_amount, 2),
      'Status' => ucfirst($record->agreement_status),
    ];
  }
}
