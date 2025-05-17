<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers\BillboardsRelationManager;
use App\Models\Billboard;
use App\Models\Contract;
use App\Models\Currency;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\FontWeight;

class ContractResource extends Resource
{
  protected static ?string $model = Contract::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-text';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 5;

  public static function form(Form $form): Form
  {
    $defaultCurrency = Currency::getDefault();
    $currencies = Currency::all();

    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Contract Details')
              ->schema([
                Forms\Components\TextInput::make('contract_number')
                  ->maxLength(255)
                  ->disabled()
                  ->dehydrated(),

                Forms\Components\Select::make('currency_code')
                  ->options(collect($currencies)->mapWithKeys(fn ($currency) =>
                  [$currency->code => "{$currency->name} ({$currency->symbol})"]
                  ))
                  ->default($defaultCurrency->code)
                  ->required(),

                Forms\Components\Select::make('client_id')
                  ->relationship('client', 'name')
                  ->required()
                  ->searchable()
                  ->preload(),

                Forms\Components\DatePicker::make('start_date')
                  ->required()
                  ->afterOrEqual('today')
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
                    function (Builder $query, Forms\Get $get) {
                      $startDate = $get('start_date');
                      $endDate = $get('end_date');

                      // Skip filtering if dates aren't selected yet
                      if (!$startDate || !$endDate) {
                        return $query;
                      }

                      return $query->whereDoesntHave('contracts', function ($q) use ($startDate, $endDate) {
                        $q->whereIn('agreement_status', ['active', 'pending'])
                          ->where(function ($q) use ($startDate, $endDate) {
                            $q->where(function ($q) use ($startDate, $endDate) {
                              // Check for overlapping date ranges
                              $q->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                            });
                          });
                      })->orderBy('name');
                    }
                  )
                  ->multiple()
                  ->required()
                  ->preload()
                  ->searchable()
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    if (empty($state)) {
                      $set('contract_total', 0);
                      $set('contract_final_amount', 0);
                      return;
                    }

                    // Get the selected billboards
                    $billboards = Billboard::whereIn('id', $state)->get();

                    // Calculate total
                    $total = $billboards->sum('base_price');

                    // Get discount if any
                    $discount = $get('contract_discount') ?? 0;

                    // Set the contract totals
                    $set('contract_total', $total);
                    $set('contract_final_amount', $total - $discount);
                  }),

                Forms\Components\TextInput::make('contract_total')
                  ->label('Total Amount')
                  ->disabled()
                  ->dehydrated()
                  ->numeric()
                  ->prefix(function (Forms\Get $get) {
                    $currencyCode = $get('currency_code');
                    $currency = Currency::where('code', $currencyCode)->first();
                    return $currency?->symbol ?? '';
                  })
                  ->default(0),

                Forms\Components\TextInput::make('contract_discount')
                  ->label('Discount Amount')
                  ->numeric()
                  ->default(0)
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    $total = $get('contract_total') ?? 0;
                    $discount = $state ?? 0;
                    $set('contract_final_amount', $total - $discount);
                  })
                  ->prefix(function (Forms\Get $get) {
                    $currencyCode = $get('currency_code');
                    $currency = Currency::where('code', $currencyCode)->first();
                    return $currency?->symbol ?? '';
                  }),

                Forms\Components\TextInput::make('contract_final_amount')
                  ->label('Final Amount')
                  ->disabled()
                  ->dehydrated()
                  ->numeric()
                  ->default(0)
                  ->prefix(function (Forms\Get $get) {
                    $currencyCode = $get('currency_code');
                    $currency = Currency::where('code', $currencyCode)->first();
                    return $currency?->symbol ?? '';
                  }),

                Forms\Components\Repeater::make('billboard_prices')
                  ->schema([
                    Forms\Components\Select::make('billboard_id')
                      ->label('Billboard')
                      ->options(function (Forms\Get $get) {
                        $selectedIds = $get('../../billboards');
                        if (!$selectedIds) return [];

                        return Billboard::whereIn('id', $selectedIds)
                          ->pluck('name', 'id');
                      })
                      ->required()
                      ->unique()
                      ->columnSpan(2),

                    Forms\Components\TextInput::make('billboard_base_price')
                      ->label('Monthly Rental')
                      ->required()
                      ->numeric()
                      ->default(function (Forms\Get $get) {
                        $billboardId = $get('billboard_id');
                        if (!$billboardId) return 0;

                        $billboard = Billboard::find($billboardId);
                        return $billboard?->base_price ?? 0;
                      })
                      ->prefix(function (Forms\Get $get) {
                        $currencyCode = $get('../../../currency_code');
                        $currency = Currency::where('code', $currencyCode)->first();
                        return $currency?->symbol ?? '';
                      })
                      ->live()
                      ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        // Recalculate total when individual price changes
                        $prices = $get('../../billboard_prices') ?? [];
                        $total = collect($prices)->sum('billboard_base_price');

                        $set('../../contract_total', $total);
                        $set('../../contract_final_amount', $total - ($get('../../contract_discount') ?? 0));
                      }),
                  ])
                  ->columns(3)
                  ->defaultItems(0)
                  ->addActionLabel('Add Billboard Price')
                  ->reorderable(false)
                  ->collapsible()
                  ->itemLabel(function (array $state): ?string {
                    $billboardId = $state['billboard_id'] ?? null;
                    if (!$billboardId) return null;

                    $billboard = Billboard::find($billboardId);
                    return $billboard?->name;
                  })
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    // Recalculate total when prices are added/removed
                    $total = collect($state)->sum('billboard_base_price');
                    $set('contract_total', $total);
                    $set('contract_final_amount', $total - ($get('contract_discount') ?? 0));
                  }),
              ])
              ->columns(2)
              ->collapsible()
              ->collapsed(false),
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
                  ->acceptedFileTypes(['application/pdf', 'image/*'])
                  ->columnSpanFull()
                  ->downloadable()
                  ->reorderable()
                  ->dehydrated(false),

                Forms\Components\SpatieMediaLibraryFileUpload::make('signed_contract')
                  ->collection('signed_contracts')
                  ->maxFiles(1)
                  ->acceptedFileTypes(['application/pdf'])
                  ->columnSpanFull()
                  ->downloadable()
                  ->dehydrated(false)
                  ->visible(fn(Forms\Get $get) => $get('agreement_status') === 'active'),
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
          ->money(function ($record) {
            $currency = Currency::where('code', $record->currency_code)->first();
            return $currency?->code ?? Currency::getDefault()->code;
          })
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_final_amount')
          ->money(function ($record) {
            $currency = Currency::where('code', $record->currency_code)->first();
            return $currency?->code ?? Currency::getDefault()->code;
          })
          ->sortable()
          ->weight(FontWeight::Bold),

        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'draft' => 'gray',
            'active' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
          }),

        Tables\Columns\TextColumn::make('start_date')
          ->date()
          ->sortable(),

        Tables\Columns\TextColumn::make('end_date')
          ->date()
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('agreement_status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ]),

        Tables\Filters\Filter::make('date_range')
          ->form([
            Forms\Components\DatePicker::make('start_from'),
            Forms\Components\DatePicker::make('start_until'),
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
        Tables\Actions\ActionGroup::make([
          Tables\Actions\ViewAction::make(),
          Tables\Actions\EditAction::make(),
          Tables\Actions\Action::make('generate_pdf')
            ->icon('heroicon-o-document-arrow-down')
            ->label('Generate PDF')
            ->action(function (Contract $record) {
              try {
                $pdf = $record->generatePdf();

                // Store the generated PDF
                $record->addMediaFromString($pdf)
                  ->usingName("Contract {$record->contract_number}")
                  ->usingFileName("{$record->contract_number}.pdf")
                  ->toMediaCollection('contract_documents');

                $record->markAsGenerated();

                Notification::make()
                  ->success()
                  ->title('PDF Generated')
                  ->body('The contract PDF has been generated successfully.')
                  ->send();

                return response()->streamDownload(
                  fn () => print($pdf),
                  "{$record->contract_number}.pdf"
                );
              } catch (\Exception $e) {
                Notification::make()
                  ->danger()
                  ->title('Error Generating PDF')
                  ->body($e->getMessage())
                  ->send();
              }
            }),
          Tables\Actions\Action::make('email_contract')
            ->icon('heroicon-o-envelope')
            ->label('Email to Client')
            ->action(function (Contract $record) {
              try {
                $record->emailToClient();

                Notification::make()
                  ->success()
                  ->title('Contract Emailed')
                  ->body('The contract has been emailed to the client successfully.')
                  ->send();
              } catch (\Exception $e) {
                Notification::make()
                  ->danger()
                  ->title('Error Sending Email')
                  ->body($e->getMessage())
                  ->send();
              }
            })
            ->requiresConfirmation()
            ->visible(fn (Contract $record) => $record->hasMedia('contract_documents')),
          Tables\Actions\Action::make('download_contract')
            ->icon('heroicon-m-arrow-down-square')
            ->label('Download Contract')
            ->visible(fn(Contract $record) => $record->hasMedia('signed_contracts'))
            ->url(fn(Contract $record) => $record->getFirstMediaUrl('signed_contracts'))
            ->openUrlInNewTab(),
        ])
          ->tooltip('Actions')
          ->iconButton()
      ])
      ->bulkActions([
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
      ]);
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
      'edit' => Pages\EditContract::route('/{record}/edit'),
      'view' => Pages\ViewContract::route('/{record}'),
      'preview' => Pages\PreviewContract::route('/{record}/preview'),
    ];
  }
}
