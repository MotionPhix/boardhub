<?php

namespace App\Filament\Resources\BillboardResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Models\Contract;
use App\Models\Currency;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractsRelationManager extends RelationManager
{
  protected static string $relationship = 'contracts';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Contract Details')
              ->schema([
                Forms\Components\Select::make('client_id')
                  ->relationship('client', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->createOptionAction(
                    fn(Action $action) => $action->modalWidth('md')
                      ->modalHeading('New Client')
                      ->modalDescription('Add new client to the contract'),
                  )
                  ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                      ->required()
                      ->placeholder('Client name')
                      ->maxLength(255),

                    Forms\Components\TextInput::make('company')
                      ->placeholder('The company represented by the client')
                      ->required()
                      ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                      ->email()
                      ->required()
                      ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                      ->tel()
                      ->maxLength(255),
                  ]),

                Forms\Components\Select::make('currency_code')
                  ->options(Currency::pluck('name', 'code'))
                  ->default(fn() => Currency::getDefault()?->code ?? 'MWK')
                  ->required(),

                Forms\Components\TextInput::make('total_amount')
                  ->numeric()
                  ->prefix(fn(Forms\Get $get) => Currency::where('code', $get('currency_code'))->value('symbol') ?? 'MK')
                  ->required()
                  ->formatStateUsing(fn($state, Forms\Get $get) => $state ?? $get('pivot_billboard_base_price'))
                  ->maxValue(42949672.95),

                Forms\Components\Select::make('agreement_status')
                  ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                  ])
                  ->required()
                  ->default('draft'),
              ])
              ->columns(2),

            Forms\Components\Section::make('Booking Details')
              ->schema([
                Forms\Components\TextInput::make('pivot_billboard_base_price')
                  ->numeric()
                  ->prefix(fn(Forms\Get $get) => Currency::where('code', $get('currency_code'))->value('symbol') ?? 'MK')
                  ->required()
                  ->label('Base Price')
                  ->default(fn() => $this->ownerRecord->base_price)
                  ->afterStateUpdated(function ($state, $set, $get) {
                    $discount = $get('pivot_billboard_discount_amount') ?? 0;
                    $set('pivot_billboard_final_price', $state - $discount);
                  }),

                Forms\Components\TextInput::make('pivot_billboard_discount_amount')
                  ->numeric()
                  ->prefix(fn(Forms\Get $get) => Currency::where('code', $get('currency_code'))->value('symbol') ?? 'MK')
                  ->default(0)
                  ->label('Discount Amount')
                  ->afterStateUpdated(function ($state, $set, $get) {
                    $basePrice = $get('pivot.billboard_base_price') ?? 0;
                    $set('pivot.billboard_final_price', $basePrice - $state);
                  }),

                Forms\Components\TextInput::make('pivot_billboard_final_price')
                  ->numeric()
                  ->prefix(fn(Forms\Get $get) => Currency::where('code', $get('currency_code'))->value('symbol') ?? 'MK')
                  ->disabled()
                  ->dehydrated(true)
                  ->default(fn(Forms\Get $get) => $get('pivot_billboard_base_price') ?? $this->ownerRecord->base_price)
                  ->label('Final Price'),

                Forms\Components\Select::make('pivot_booking_status')
                  ->options(collect(BookingStatus::cases())->mapWithKeys(fn(BookingStatus $status) => [
                    $status->value => $status->label()
                  ]))
                  ->required()
                  ->default(BookingStatus::PENDING->value)
                  ->label('Booking Status'),

                Forms\Components\Textarea::make('pivot_notes')
                  ->maxLength(65535)
                  ->columnSpanFull()
                  ->label('Booking Notes'),
              ])
              ->columns(2),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Documents')
              ->description('Upload contract documents and signed agreements')
              ->schema([
                Forms\Components\Grid::make()
                  ->schema([
                    Forms\Components\Section::make('Contract Documents')
                      ->description('Upload any supporting documents (max 5 files)')
                      ->icon('heroicon-o-document-duplicate')
                      ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('contract_documents')
                          ->collection('contract_documents')
                          ->multiple()
                          ->maxFiles(5)
                          ->downloadable()
                          ->reorderable()
                          ->acceptedFileTypes([
                            'application/pdf',
                            'image/*',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                          ])
                          ->hint('PDF, Word documents and images only')
                          ->columnSpanFull(),
                      ]),

                    Forms\Components\Section::make('Signed Contract')
                      ->description('Upload the final signed contract (PDF only)')
                      ->icon('heroicon-o-document-check')
                      ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('signed_contract')
                          ->collection('signed_contracts')
                          ->downloadable()
                          ->maxFiles(1)
                          ->acceptedFileTypes(['application/pdf'])
                          ->hint('PDF files only')
                          ->columnSpanFull(),
                      ]),
                  ])
                  ->columns(1),

                // Add a view of existing documents with download buttons
                Forms\Components\View::make('filament.components.document-preview')
                  ->view('filament.components.document-preview')
                  ->viewData([
                    'record' => fn () => $this->ownerRecord,
                  ])
                  ->visible(fn () => ($this->ownerRecord->hasMedia('contract_documents') || $this->ownerRecord->hasMedia('signed_contracts')))
                  ->columnSpanFull(),
              ])
              ->collapsible()
              ->collapsed(false),
          ])
          ->columnSpan(['lg' => 1]),
      ])
      ->columns(3);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('contract_number')
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('total_amount')
          ->money(fn(Contract $record): string => $record->currency_code)
          ->sortable(),

        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->colors([
            'danger' => 'cancelled',
            'warning' => 'draft',
            'success' => 'active',
            'gray' => 'completed',
          ]),

        Tables\Columns\TextColumn::make('pivot.billboard_base_price')
          ->money(fn(Contract $record): string => $record->currency_code)
          ->label('Base Price')
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_discount_amount')
          ->money(fn(Contract $record): string => $record->currency_code)
          ->label('Discount')
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_final_price')
          ->money(fn(Contract $record): string => $record->currency_code)
          ->label('Final Price')
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.booking_status')
          ->badge()
          ->formatStateUsing(function ($state) {
            if (!$state) {
              return 'N/A';
            }
            $status = is_string($state) ? $state : $state->value;
            return BookingStatus::from($status)->label();
          })
          ->color(function ($state) {
            if (!$state) {
              return 'gray';
            }
            $status = is_string($state) ? $state : $state->value;
            return BookingStatus::from($status)->color();
          }),
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
          ->options(collect(BookingStatus::cases())->mapWithKeys(fn(BookingStatus $status) => [
            $status->value => $status->label()
          ])),

        Tables\Filters\Filter::make('active')
          ->query(fn(Builder $query): Builder => $query
            ->where('agreement_status', 'active')
            ->where('booking_status', BookingStatus::IN_USE->value))
          ->toggle(),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->after(function ($data, $record) {
            if ($record->agreement_status === 'active') {
              $this->ownerRecord->update([
                'physical_status' => 'operational',
              ]);
            }
          }),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Booking Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('booking_status')
                ->label('New Booking Status')
                ->options(collect(BookingStatus::cases())->mapWithKeys(fn(BookingStatus $status) => [
                  $status->value => $status->label()
                ]))
                ->required(),
            ])
            ->action(function (array $data, $records) {
              $records->each(function ($record) use ($data) {
                $record->pivot->update([
                  'booking_status' => $data['booking_status'],
                ]);
              });
            }),
        ]),
      ]);
  }
}
