<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers\BillboardsRelationManager;
use App\Models\Billboard;
use App\Models\Contract;
use App\Models\ContractTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class ContractResource extends Resource
{
  protected static ?string $model = Contract::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-text';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 5;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Contract Details')
              ->schema([
                Forms\Components\TextInput::make('contract_number')
                  ->disabled()
                  ->dehydrated()
                  ->placeholder('This will be auto-generated')
                  ->formatStateUsing(function ($state) {
                    return $state ?: '';
                  }),

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
                  ->relationship(
                    'billboards',
                    'name',
                    function (Builder $query, Forms\Get $get) {
                      $startDate = $get('start_date');
                      $endDate = $get('end_date');
                      $contractId = $get('id');

                      if (!$startDate || !$endDate) {
                        return $query;
                      }

                      return $query->where(function ($query) use ($startDate, $endDate, $contractId) {
                        $query->whereDoesntHave('contracts', function ($q) use ($startDate, $endDate, $contractId) {
                          $q->where('contracts.id', '!=', $contractId ?: 0)
                            ->whereIn('agreement_status', ['active', 'pending'])
                            ->where(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                            });
                        })
                          ->orWhereHas('contracts', function ($q) use ($contractId) {
                            $q->where('contracts.id', $contractId);
                          });
                      });
                    }
                  )
                  ->multiple()
                  ->required()
                  ->preload()
                  ->searchable()
                  ->live()
                  ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (empty($state)) {
                      $set('contract_total', 0);
                      $set('contract_final_amount', 0);
                      return;
                    }

                    $total = Billboard::whereIn('id', $state)->sum('base_price');

                    $set('contract_total', $total);
                    $set('contract_final_amount', $total);
                  }),

                Forms\Components\TextInput::make('contract_total')
                  ->label('Total Amount')
                  ->disabled()
                  ->dehydrated()
                  ->numeric()
                  ->prefix('K')
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
                  ->prefix('K'),

                Forms\Components\TextInput::make('contract_final_amount')
                  ->label('Final Amount')
                  ->disabled()
                  ->dehydrated()
                  ->numeric()
                  ->default(0)
                  ->prefix('K'),
              ])
              ->columns(2)
              ->collapsible(),
          ])
          ->columnSpan(['lg' => 2]),

        // Template Picker
        Forms\Components\Section::make('Contract Template')
          ->schema([
            Forms\Components\Select::make('template_id')
              ->label('Contract Template')
              ->options(function () {
                return ContractTemplate::active()
                  ->get()
                  ->mapWithKeys(function ($template) {
                    return [$template->id => $template->name];
                  });
              })
              ->default(function () {
                return ContractTemplate::getDefaultTemplate()?->id;
              })
              ->reactive()
              ->afterStateUpdated(function ($state, callable $set) {
                if ($state) {
                  $template = ContractTemplate::find($state);
                  if ($template) {
                    // You might want to update other form fields based on the template
                    $set('contract_terms', $template->content);
                  }
                }
              })
              ->required()
              ->helperText('Select a template for this contract')
              ->columnSpanFull(),

            Forms\Components\Grid::make()
              ->schema([
                Forms\Components\View::make('filament.components.template-preview')
                  ->visible(fn (Forms\Get $get) => $get('template_id'))
                  ->viewData(fn (Forms\Get $get) => [
                    'template' => ContractTemplate::find($get('template_id')),
                  ])
              ])
              ->columns(1),
          ])
          ->collapsible(),
        // End Template Picker

        // Document Management
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
    // End Document Management
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('client.company')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_total')
          ->money('MWK')
          ->sortable(),

        Tables\Columns\TextColumn::make('contract_final_amount')
          ->money('MWK')
          ->sortable()
          ->weight(FontWeight::Bold),

        Tables\Columns\TextColumn::make('agreement_status')
          ->badge()
          ->formatStateUsing(fn ($state): string => strtoupper($state))
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
                $pdf = $record->generateDownloadablePdf();

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
