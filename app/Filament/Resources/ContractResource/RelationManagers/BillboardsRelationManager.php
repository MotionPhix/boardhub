<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Models\Billboard;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillboardsRelationManager extends RelationManager
{
  protected static string $relationship = 'billboards';
  protected static ?string $title = 'Contract Billboards';

  protected function getCurrencyForBillboard(Billboard $billboard = null): array
  {
    // First try to get from billboard
    if ($billboard && $billboard->currency_code) {
      $currencies = Settings::getAvailableCurrencies();
      if (isset($currencies[$billboard->currency_code])) {
        return $currencies[$billboard->currency_code];
      }
    }

    // Then try to get from contract
    if ($this->ownerRecord && $this->ownerRecord->currency_code) {
      $currencies = Settings::getAvailableCurrencies();
      if (isset($currencies[$this->ownerRecord->currency_code])) {
        return $currencies[$this->ownerRecord->currency_code];
      }
    }

    // Finally fall back to default currency
    return Settings::getDefaultCurrency();
  }

  protected function getAvailableBillboards()
  {
    return Billboard::query()
      ->whereDoesntHave('contracts', function ($query) {
        $query->where('agreement_status', 'active')
          ->wherePivot('booking_status', 'in_use');
      })
      ->orWhereHas('contracts', function ($query) {
        $query->where('contract_id', $this->ownerRecord->id);
      });
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make()
          ->schema([
            Forms\Components\Select::make('billboard_id')
              ->label('Billboard')
              ->options($this->getAvailableBillboards()->pluck('name', 'id'))
              ->searchable()
              ->preload()
              ->required()
              ->live()
              ->afterStateUpdated(function ($state, Forms\Set $set) {
                if ($state) {
                  $billboard = Billboard::find($state);
                  $currency = $this->getCurrencyForBillboard($billboard);

                  $set('billboard_base_price', $billboard?->base_price ?? 0);
                  $set('billboard_final_price', $billboard?->base_price ?? 0);
                  $set('currency_code', $currency['code']);
                  $set('currency_symbol', $currency['symbol']);
                }
              }),

            Forms\Components\Grid::make(3)
              ->schema([
                Forms\Components\TextInput::make('billboard_base_price')
                  ->label('Base Price')
                  ->disabled()
                  ->numeric()
                  ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
                  ->required(),

                Forms\Components\TextInput::make('billboard_discount_amount')
                  ->label('Discount')
                  ->numeric()
                  ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
                  ->default(0)
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    $basePrice = $get('billboard_base_price') ?? 0;
                    $discount = $state ?? 0;
                    $set('billboard_final_price', max(0, $basePrice - $discount));
                  }),

                Forms\Components\TextInput::make('billboard_final_price')
                  ->label('Final Price')
                  ->disabled()
                  ->numeric()
                  ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
                  ->required(),

                // Hidden fields to store currency info
                Forms\Components\Hidden::make('currency_code'),
                Forms\Components\Hidden::make('currency_symbol'),
              ]),

            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\Select::make('booking_status')
                  ->options([
                    BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
                    BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                    BookingStatus::IN_USE->value => BookingStatus::IN_USE->label(),
                    BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
                    BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
                  ])
                  ->default(
                    $this->ownerRecord->agreement_status === 'active'
                      ? BookingStatus::IN_USE->value
                      : BookingStatus::PENDING->value
                  )
                  ->required(),

                Forms\Components\Textarea::make('notes')
                  ->maxLength(65535),
              ]),
          ]),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        /*Tables\Columns\SpatieMediaLibraryImageColumn::make('billboard_images')
          ->label('')
          ->collection('billboard_images')
          ->square()
          ->height(50)
          ->limit(1),*/

        // Replace the existing 'name' column with this:
        Tables\Columns\TextColumn::make('size')
          ->formatStateUsing(function ($record) {
            $lines = [
              // Line 1: Size
              "<div class='text-sm font-medium'>{$record->size}</div>",

              // Line 2: Location name
              "<div class='text-sm text-gray-400'>
                {$record->name}, {$record->location->name}
              </div>",

              // Line 3: City, State, Country
              "<div class='text-sm text-gray-400'>" .
              implode(' · ', array_filter([
                $record->location->city?->name,
                $record->location->state?->name,
                $record->location->country?->name
              ])) .
              "</div>"
            ];

            return implode('', $lines);
          })
          ->html()
          ->searchable(query: function (Builder $query, string $search): Builder {
            return $query->where('billboards.name', 'like', "%{$search}%")
              ->orWhere('billboards.size', 'like', "%{$search}%")
              ->orWhereHas('location', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                  ->orWhereHas('city', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('state', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('country', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                  });
              });
          })
          ->sortable()
          ->label('Billboard Details'),

        Tables\Columns\TextColumn::make('pivot.billboard_base_price')
          ->label('Base Price')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)['code'])
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_discount_amount')
          ->label('Discount')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)['code'])
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_final_price')
          ->label('Final Price')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)['code'])
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.booking_status')
          ->label('Booking Status')
          ->badge()
          ->colors([
            'warning' => 'pending',
            'info' => 'confirmed',
            'success' => 'in_use',
            'gray' => 'completed',
            'danger' => 'cancelled',
          ]),

        Tables\Columns\TextColumn::make('pivot.created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('booking_status')
          ->options([
            BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
            BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
            BookingStatus::IN_USE->value => BookingStatus::IN_USE->label(),
            BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
            BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
          ]),
      ])
      ->headerActions([
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->form(fn (Tables\Actions\AttachAction $action): array => [
            $action->getRecordSelect()
              ->label('Billboard')
              ->options($this->getAvailableBillboards()->pluck('name', 'id'))
              ->searchable()
              ->preload()
              ->required()
              ->live()
              ->afterStateUpdated(function ($state, Forms\Set $set) {
                if ($state) {
                  $billboard = Billboard::find($state);
                  $currency = $this->getCurrencyForBillboard($billboard);

                  $set('billboard_base_price', $billboard?->base_price ?? 0);
                  $set('billboard_final_price', $billboard?->base_price ?? 0);
                  $set('currency_code', $currency['code']);
                  $set('currency_symbol', $currency['symbol']);
                }
              }),

            Forms\Components\TextInput::make('billboard_base_price')
              ->label('Base Price')
              ->disabled()
              ->numeric()
              ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
              ->required(),

            Forms\Components\TextInput::make('billboard_discount_amount')
              ->label('Discount')
              ->numeric()
              ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
              ->default(0)
              ->live()
              ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                $basePrice = $get('billboard_base_price') ?? 0;
                $discount = $state ?? 0;
                $set('billboard_final_price', max(0, $basePrice - $discount));
              }),

            Forms\Components\TextInput::make('billboard_final_price')
              ->label('Final Price')
              ->disabled()
              ->numeric()
              ->prefix(fn (Forms\Get $get) => $get('currency_symbol') ?? 'MK')
              ->required(),

            Forms\Components\Hidden::make('currency_code'),
            Forms\Components\Hidden::make('currency_symbol'),

            Forms\Components\Select::make('booking_status')
              ->options([
                BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
                BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                BookingStatus::IN_USE->value => BookingStatus::IN_USE->label(),
                BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
                BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
              ])
              ->default(
                $this->ownerRecord->agreement_status === 'active'
                  ? BookingStatus::IN_USE->value
                  : BookingStatus::PENDING->value
              )
              ->required(),

            Forms\Components\Textarea::make('notes')
              ->maxLength(65535),
          ]),
      ])
      ->actions([
        Tables\Actions\EditAction::make()
          ->form([
            Forms\Components\TextInput::make('billboard_base_price')
              ->label('Base Price')
              ->disabled()
              ->numeric()
              ->prefix(fn ($record) => $this->getCurrencyForBillboard($record)['symbol'])
              ->required(),

            Forms\Components\TextInput::make('billboard_discount_amount')
              ->label('Discount')
              ->numeric()
              ->prefix(fn ($record) => $this->getCurrencyForBillboard($record)['symbol'])
              ->live()
              ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                $basePrice = $get('billboard_base_price') ?? 0;
                $discount = $state ?? 0;
                $set('billboard_final_price', max(0, $basePrice - $discount));
              }),

            Forms\Components\TextInput::make('billboard_final_price')
              ->label('Final Price')
              ->disabled()
              ->numeric()
              ->prefix(fn ($record) => $this->getCurrencyForBillboard($record)['symbol'])
              ->required(),

            Forms\Components\Select::make('booking_status')
              ->options([
                BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
                BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                BookingStatus::IN_USE->value => BookingStatus::IN_USE->label(),
                BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
                BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
              ])
              ->required(),

            Forms\Components\Textarea::make('notes')
              ->maxLength(65535),
          ]),

        Tables\Actions\DetachAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make(),
          Tables\Actions\BulkAction::make('updateStatus')
            ->label('Update Booking Status')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
              Forms\Components\Select::make('booking_status')
                ->label('New Booking Status')
                ->options([
                  BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
                  BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                  BookingStatus::IN_USE->value => BookingStatus::IN_USE->label(),
                  BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
                  BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
                ])
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
