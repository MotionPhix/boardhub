<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Settings;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Number;

class ViewContract extends ViewRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->icon('heroicon-m-pencil-square'),
      Actions\Action::make('download_contract')
        ->icon('heroicon-m-arrow-down-on-square')
        ->label('Download Contract')
        ->color('success')
        ->visible(fn () => $this->record->hasMedia('signed_contracts'))
        ->url(fn () => $this->record->getFirstMediaUrl('signed_contracts'))
        ->openUrlInNewTab(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    $defaultCurrency = Settings::getDefaultCurrency();

    return $infolist
      ->schema([
        // Contract Overview Section
        Components\Section::make([
          Components\TextEntry::make('contract_number')
            ->label('Contract Number')
            ->weight(FontWeight::Bold)
            ->size(Components\TextEntry\TextEntrySize::Large)
            ->copyable()
            ->copyMessage('Contract number copied!')
            ->copyMessageDuration(1500),

          Components\Grid::make(2)
            ->schema([
              Components\TextEntry::make('client.name')
                ->label('Client')
                ->copyable(),

              Components\TextEntry::make('client.company')
                ->visible(fn ($record) => !empty($record->client->company)),

              Components\TextEntry::make('start_date')
                ->date('d M Y'),

              Components\TextEntry::make('end_date')
                ->date('d M Y'),
            ]),
        ])
          ->columnSpan(['lg' => 2]),

        // Financial Details Section
        Components\Section::make('Financial Details')
          ->schema([
            Components\Grid::make(2)
              ->schema([
                Components\TextEntry::make('base_price')
                  ->label('Base Price')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency['code'])
                  ->color('gray'),

                Components\TextEntry::make('discount_amount')
                  ->label('Discount')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency['code'])
                  ->color('gray'),

                Components\TextEntry::make('total_amount')
                  ->label('Total Amount')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency['code'])
                  ->color('primary')
                  ->weight(FontWeight::Bold),

                Components\TextEntry::make('payment_terms')
                  ->label('Payment Terms'),
              ]),
          ])
          ->columnSpan(['lg' => 1]),

        // Billboards Section
        Components\Section::make('Billboards')
          ->description('Billboards included in this contract')
          ->schema([
            Components\RepeatableEntry::make('billboards')
              ->schema([
                Components\TextEntry::make('name')
                  ->weight(FontWeight::Bold)
                  ->url(fn ($record) => route('filament.auth.resources.billboards.view', $record))
                  ->openUrlInNewTab(),

                Components\TextEntry::make('pivot.base_price')
                  ->label('Base Price')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency['code']),

                Components\TextEntry::make('pivot.final_price')
                  ->label('Final Price')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency['code']),

                Components\TextEntry::make('pivot.booking_status')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'in_use' => 'success',
                    'completed' => 'gray',
                    'cancelled' => 'danger',
                    default => 'warning',
                  }),
              ])
              ->columns(4),
          ])
          ->collapsible()
          ->collapsed(false)
          ->columnSpan('full'),

        // Documents Section
        Components\Section::make('Documents')
          ->schema([
            Components\Split::make([
              Components\Grid::make(1)
                ->schema([
                  Components\TextEntry::make('')
                    ->label('Contract Documents')
                    ->html(fn ($record) => $this->renderContractDocuments($record))
                    ->visible(fn ($record) => $record->hasMedia('contract_documents')),

                  Components\TextEntry::make('')
                    ->label('Signed Contract')
                    ->html(fn ($record) => $this->renderSignedContract($record))
                    ->visible(fn ($record) => $record->hasMedia('signed_contracts')),
                ]),
            ]),
          ])
          ->collapsible()
          ->collapsed(false)
          ->columnSpan('full')
          ->hidden(fn ($record) => !$record->hasMedia('contract_documents') && !$record->hasMedia('signed_contracts')),

        // Notes Section
        Components\Section::make('Notes')
          ->schema([
            Components\TextEntry::make('notes')
              ->markdown()
              ->prose(),
          ])
          ->collapsible()
          ->collapsed()
          ->columnSpan('full')
          ->hidden(fn ($record) => empty($record->notes)),

        // Record History Section
        Components\Section::make('Record History')
          ->schema([
            Components\Grid::make(2)
              ->schema([
                Components\TextEntry::make('created_at')
                  ->dateTime('M d, Y H:i')
                  ->label('Created')
                  ->color('gray'),

                Components\TextEntry::make('updated_at')
                  ->dateTime('M d, Y H:i')
                  ->label('Last Updated')
                  ->color('gray'),
              ]),
          ])
          ->collapsible()
          ->collapsed()
          ->columnSpan('full'),
      ])
      ->columns(3);
  }

  protected function renderContractDocuments($record): string
  {
    $html = '<div class="space-y-4">';

    foreach ($record->getMedia('contract_documents') as $media) {
      $html .= '
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                ' . e($media->file_name) . '
                            </p>
                            <p class="text-sm text-gray-500">
                                ' . Number::fileSize($media->size) . ' • Uploaded ' . $media->created_at->format('M d, Y') . '
                            </p>
                        </div>
                    </div>
                    <a href="' . $media->getUrl() . '" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-2 focus:ring-primary-600">
                        Download
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                        </svg>
                    </a>
                </div>';
    }

    $html .= '</div>';

    return $html;
  }

  protected function renderSignedContract($record): string
  {
    $media = $record->getFirstMedia('signed_contracts');

    if (!$media) {
      return '';
    }

    return '
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-4">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            ' . e($media->file_name) . '
                        </p>
                        <p class="text-sm text-gray-500">
                            ' . Number::fileSize($media->size) . ' • Uploaded ' . $media->created_at->format('M d, Y') . '
                        </p>
                    </div>
                </div>
                <a href="' . $media->getUrl() . '" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-2 focus:ring-primary-600">
                    Download
                    <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                        <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                    </svg>
                </a>
            </div>';
  }
}
