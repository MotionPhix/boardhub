<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Models\Billboard;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BillboardsRelationManager extends RelationManager
{
  protected static string $relationship = 'billboards';

  protected static ?string $title = 'Contract Billboards';

  protected static ?string $recordTitleAttribute = 'name';

  protected function getCurrencyForBillboard(Billboard $billboard = null): Currency
  {
    static $currencyCache = [];

    // Try billboard currency
    if ($billboard && $billboard->currency_code) {
      $cacheKey = "billboard_{$billboard->currency_code}";
      if (!isset($currencyCache[$cacheKey])) {
        $currencyCache[$cacheKey] = Currency::where('code', $billboard->currency_code)->first();
      }
      if ($currencyCache[$cacheKey]) {
        return $currencyCache[$cacheKey];
      }
    }

    // Try contract currency
    if ($this->ownerRecord && $this->ownerRecord->currency_code) {
      $cacheKey = "contract_{$this->ownerRecord->currency_code}";
      if (!isset($currencyCache[$cacheKey])) {
        $currencyCache[$cacheKey] = Currency::where('code', $this->ownerRecord->currency_code)->first();
      }
      if ($currencyCache[$cacheKey]) {
        return $currencyCache[$cacheKey];
      }
    }

    // Default currency
    if (!isset($currencyCache['default'])) {
      $currencyCache['default'] = Currency::getDefault();
    }
    return $currencyCache['default'];
  }

  protected function getAvailableBillboards(): Collection
  {
    return Billboard::query()
      ->select('billboards.*') // Be explicit about selected columns
      ->where('is_active', true)
      ->where(function (Builder $query) {
        $query->whereDoesntHave('contracts', function (Builder $query) {
          $query->where('agreement_status', 'active')
            ->wherePivot('booking_status', 'in_use')
            ->wherePivotNotIn('contract_id', [$this->ownerRecord->id]);
        })
          ->orWhereHas('contracts', function (Builder $query) {
            $query->where('contract_id', $this->ownerRecord->id);
          });
      })
      ->with(['location.city', 'location.state', 'location.country']) // Eager load relationships
      ->orderBy('name')
      ->get();
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
                  if ($billboard) {
                    $currency = $this->getCurrencyForBillboard($billboard);

                    $set('billboard_base_price', $billboard->base_price ?? 0);
                    $set('billboard_final_price', $billboard->base_price ?? 0);
                    $set('currency_code', $currency->code);
                  }
                }
              }),

            Forms\Components\Grid::make(3)
              ->schema([
                Forms\Components\TextInput::make('billboard_base_price')
                  ->label('Base Price')
                  ->disabled()
                  ->numeric()
                  ->prefix(fn (Forms\Get $get) =>
                    Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
                  )
                  ->required(),

                Forms\Components\TextInput::make('billboard_discount_amount')
                  ->label('Discount')
                  ->numeric()
                  ->prefix(fn (Forms\Get $get) =>
                    Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
                  )
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
                  ->prefix(fn (Forms\Get $get) =>
                    Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
                  )
                  ->required(),

                Forms\Components\Hidden::make('currency_code'),
              ]),

            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\Select::make('booking_status')
                  ->options(BookingStatus::class)
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
        Tables\Columns\SpatieMediaLibraryImageColumn::make('billboard_images')
          ->label('')
          ->collection('billboard_images')
          ->square()
          ->height(50)
          ->limit(1),

        Tables\Columns\TextColumn::make('size')
          ->formatStateUsing(function ($record) {
            return view('filament.components.billboard-details', [
              'billboard' => $record
            ])->render();
          })
          ->html()
          ->searchable(query: function (Builder $query, string $search): Builder {
            return $query->where('billboards.name', 'like', "%{$search}%")
              ->orWhere('billboards.size', 'like', "%{$search}%")
              ->orWhereHas('location', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                  ->orWhereHas('city', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('state', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('country', fn($q) => $q->where('name', 'like', "%{$search}%"));
              });
          })
          ->sortable()
          ->label('Billboard Details'),

        Tables\Columns\TextColumn::make('pivot.billboard_base_price')
          ->label('Base Price')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)->code)
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_discount_amount')
          ->label('Discount')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)->code)
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.billboard_final_price')
          ->label('Final Price')
          ->money(fn ($record) => $this->getCurrencyForBillboard($record)->code)
          ->sortable(),

        Tables\Columns\TextColumn::make('pivot.booking_status')
          ->badge()
          ->colors([
            'warning' => 'pending',
            'info' => 'confirmed',
            'success' => 'in_use',
            'gray' => 'completed',
            'danger' => 'cancelled',
          ]),

        Tables\Columns\TextColumn::make('pivot.created_at')
          ->dateTime(config('app.datetime_format', 'M d, Y H:i'))
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('booking_status')
          ->options(BookingStatus::class),
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
                  if ($billboard) {
                    $currency = $this->getCurrencyForBillboard($billboard);

                    $set('billboard_base_price', $billboard->base_price ?? 0);
                    $set('billboard_final_price', $billboard->base_price ?? 0);
                    $set('currency_code', $currency->code);
                  }
                }
              }),

            Forms\Components\TextInput::make('billboard_base_price')
              ->label('Base Price')
              ->disabled()
              ->numeric()
              ->prefix(fn (Forms\Get $get) =>
                Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
              )
              ->required(),

            Forms\Components\TextInput::make('billboard_discount_amount')
              ->label('Discount')
              ->numeric()
              ->prefix(fn (Forms\Get $get) =>
                Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
              )
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
              ->prefix(fn (Forms\Get $get) =>
                Currency::where('code', $get('currency_code'))->first()?->symbol ?? 'K'
              )
              ->required(),

            Forms\Components\Hidden::make('currency_code'),

            Forms\Components\Select::make('booking_status')
              ->options(BookingStatus::class)
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
        Tables\Actions\EditAction::make(),
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
                ->options(BookingStatus::class)
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
