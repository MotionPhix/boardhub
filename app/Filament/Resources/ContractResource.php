<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers\BillboardsRelationManager;
use App\Models\Contract;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
                  ->default(fn() => 'CNT-' . date('Y') . '-' .
                    str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT))
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
                  $set('end_date', $state ? date('Y-m-d', strtotime($state . ' +1 month')) : null)),

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
                  ->relationship(
                    'billboards',
                    'name',
                    function ($query) {
                      return $query->select([
                        'billboards.id',
                        'billboards.name',
                        'billboards.base_price'
                      ]);
                    }
                  )
                  ->multiple()
                  ->preload()
                  ->searchable()
                  ->required()
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    if (empty($state)) {
                      $set('contract_total', 0);
                      $set('contract_discount', 0);
                      $set('contract_final_amount', 0);
                      return;
                    }

                    $baseAmount = \App\Models\Billboard::whereIn('billboards.id', $state)
                      ->sum('billboards.base_price');

                    $set('contract_total', $baseAmount);

                    // Recalculate total with any existing discount
                    $discountAmount = $get('contract_discount') ?? 0;
                    $set('contract_final_amount', $baseAmount - $discountAmount);
                  }),

                Forms\Components\Grid::make(3)
                  ->schema([
                    Forms\Components\TextInput::make('contract_total')
                      ->label('Total Price')
                      ->numeric()
                      ->prefix($currency['symbol'])
                      ->disabled()
                      ->dehydrated()
                      ->required(),

                    Forms\Components\TextInput::make('contract_discount')
                      ->label('Discount Amount')
                      ->numeric()
                      ->prefix($currency['symbol'])
                      ->default(0)
                      ->live()
                      ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $totalAmount = $get('contract_total') ?? 0;
                        $discountAmount = $state ?? 0;

                        if ($discountAmount > $totalAmount) {
                          $set('contract_discount', $totalAmount);
                          $discountAmount = $totalAmount;
                        }

                        $set('contract_final_amount', $totalAmount - $discountAmount);
                      }),

                    Forms\Components\TextInput::make('contract_final_amount')
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
                  ->columnSpanFull()
                  ->downloadable()
                  ->reorderable()
                  ->dehydrated(false) // Only save when actually changed
                  ->afterStateUpdated(function ($state) {
                    // Only validate if there are new files
                    if ($state) {
                      return;
                    }
                  }),

                Forms\Components\SpatieMediaLibraryFileUpload::make('signed_contract')
                  ->collection('signed_contracts')
                  ->maxFiles(1)
                  ->acceptedFileTypes(['application/pdf'])
                  ->columnSpanFull()
                  ->downloadable()
                  ->dehydrated(false) // Only save when actually changed
                  ->visible(fn(Forms\Get $get) => $get('agreement_status') === 'active')
                  ->afterStateUpdated(function ($state) {
                    // Only validate if there are new files
                    if ($state) {
                      return;
                    }
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
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_total')
          ->money(fn ($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_discount')
          ->money(fn ($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_final_amount')
          ->money(fn ($record) => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'draft' => 'warning',
            'active' => 'success',
            'completed' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
          }),

        Tables\Columns\TextColumn::make('billboards_count')
          ->counts('billboards')
          ->label('Billboards')
          ->sortable(),

        Tables\Columns\TextColumn::make('start_date')
          ->date()
          ->sortable()
          ->toggleable(),

        Tables\Columns\TextColumn::make('end_date')
          ->date()
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

        Tables\Filters\Filter::make('date_range')
          ->form([
            Forms\Components\DatePicker::make('start_from')
              ->label('Start Date From'),
            Forms\Components\DatePicker::make('start_until')
              ->label('Start Date Until'),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['start_from'],
                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
              )
              ->when(
                $data['start_until'],
                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
              );
          }),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('download_contract')
          ->icon('heroicon-m-arrow-down-on-square')
          ->label('Download')
          ->visible(fn(Contract $record) => $record->hasMedia('signed_contracts'))
          ->url(fn(Contract $record) => $record->getFirstMediaUrl('signed_contracts'))
          ->openUrlInNewTab(),
      ])
      ->bulkActions([
        Tables\Actions\Action::make('generate_pdf')
          ->icon('heroicon-o-document-arrow-down')
          ->label('Generate PDF')
          ->action(function (Contract $record) {
            return response()->streamDownload(
              fn () => print($record->generatePdf()),
              "{$record->contract_number}.pdf"
            );
          }),

        Tables\Actions\Action::make('email_contract')
          ->icon('heroicon-o-envelope')
          ->label('Email to Client')
          ->action(function (Contract $record) {
            $record->emailToClient();
            Notification::make()
              ->title('Contract Emailed')
              ->success()
              ->send();
          })
          ->requiresConfirmation()
          ->visible(fn (Contract $record) => $record->client->email !== null),

        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Status')
            ->icon('heroicon-m-arrow-path')
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
                $record->billboards()->updateExistingPivot(
                  $record->billboards->pluck('id'),
                  [
                    'booking_status' => match($data['agreement_status']) {
                      'active' => 'in_use',
                      'completed' => 'completed',
                      'cancelled' => 'cancelled',
                      default => 'available',
                    }
                  ]
                );
              });
            }),
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
      'index' => Pages\ListContracts::route('/'),
      'create' => Pages\CreateContract::route('/create'),
      'view' => Pages\ViewContract::route('/{record}'),
      'edit' => Pages\EditContract::route('/{record}/edit'),
    ];
  }
}
