<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Currency;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\View;

class ViewContract extends ViewRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->icon('heroicon-m-pencil-square'),

      Actions\Action::make('preview_contract')
        ->icon('heroicon-m-eye')
        ->label('Preview')
        ->color('gray')
        ->url(fn () => route('filament.admin.resources.contracts.preview', ['record' => $this->record]))
        ->openUrlInNewTab(),

      Actions\Action::make('download_contract')
        ->icon('heroicon-m-arrow-down-square')
        ->label('Download Contract')
        ->color('success')
        ->visible(fn () => $this->record->hasMedia('signed_contracts'))
        ->url(fn () => $this->record->getFirstMediaUrl('signed_contracts'))
        ->openUrlInNewTab(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        // Contract Overview Section
        Components\Section::make()
          ->schema([
            Components\Split::make([
              Components\Grid::make(2)
                ->schema([
                  Components\TextEntry::make('contract_number')
                    ->label('Contract Number')
                    ->weight(FontWeight::Bold)
                    ->size(Components\TextEntry\TextEntrySize::Large)
                    ->copyable()
                    ->copyMessage('Contract number copied!')
                    ->copyMessageDuration(1500),

                  Components\TextEntry::make('agreement_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                      'draft' => 'warning',
                      'active' => 'success',
                      'completed' => 'info',
                      'cancelled' => 'danger',
                      default => 'gray',
                    }),
                ]),
            ])->from('lg'),

            Components\Grid::make(2)
              ->schema([
                Components\TextEntry::make('client.name')
                  ->label('Client')
                  ->url(fn ($record) => route('filament.admin.resources.clients.view', ['record' => $record->client]))
                  ->openUrlInNewTab(),

                Components\TextEntry::make('client.company')
                  ->visible(fn ($record) => filled($record->client->company)),

                Components\TextEntry::make('start_date')
                  ->date(config('app.date_format', 'M d, Y')),

                Components\TextEntry::make('end_date')
                  ->date(config('app.date_format', 'M d, Y')),

                Components\TextEntry::make('contract_total')
                  ->label('Total Price')
                  ->money(fn ($record) => $record->currency_code)
                  ->color('gray'),

                Components\TextEntry::make('contract_discount')
                  ->label('Discount')
                  ->money(fn ($record) => $record->currency_code)
                  ->color('gray'),

                Components\TextEntry::make('contract_final_amount')
                  ->label('Final Amount')
                  ->money(fn ($record) => $record->currency_code)
                  ->weight(FontWeight::Bold),

                Components\TextEntry::make('currency.name')
                  ->label('Currency'),
              ]),

            Components\Grid::make(1)
              ->schema([
                Components\TextEntry::make('notes')
                  ->markdown()
                  ->prose(),
              ])
              ->visible(fn ($record) => filled($record->notes)),
          ])
          ->columnSpan(['lg' => 2]),

        // Documents Section
        Components\Section::make('Documents')
          ->schema([
            Components\Grid::make(1)
              ->schema([
                Components\TextEntry::make('documents')
                  ->label('Contract Documents')
                  ->html(fn () => $this->renderDocumentsList('contract_documents'))
                  ->visible(fn () => $this->record->hasMedia('contract_documents')),

                Components\TextEntry::make('signed_contract')
                  ->label('Signed Contract')
                  ->html(fn () => $this->renderDocumentsList('signed_contracts'))
                  ->visible(fn () => $this->record->hasMedia('signed_contracts')),
              ]),
          ])
          ->collapsible()
          ->collapsed(false)
          ->columnSpan(['lg' => 1]),

        // Record History Section
        Components\Section::make('Record History')
          ->schema([
            Components\Grid::make(2)
              ->schema([
                Components\TextEntry::make('created_at')
                  ->dateTime(config('app.datetime_format', 'M d, Y H:i'))
                  ->label('Created')
                  ->color('gray'),

                Components\TextEntry::make('updated_at')
                  ->dateTime(config('app.datetime_format', 'M d, Y H:i'))
                  ->label('Last Updated')
                  ->color('gray'),

                Components\TextEntry::make('created_by')
                  ->label('Created By')
                  ->getStateUsing(fn ($record) => $record->creator?->name ?? 'System')
                  ->visible(fn ($record) => $record->created_by),

                Components\TextEntry::make('updated_by')
                  ->label('Last Updated By')
                  ->getStateUsing(fn ($record) => $record->updater?->name ?? 'System')
                  ->visible(fn ($record) => $record->updated_by),
              ]),
          ])
          ->collapsible()
          ->columnSpan(2),
      ])
      ->columns(3);
  }

  protected function renderDocumentsList(string $collection): string
  {
    if (!$this->record->hasMedia($collection)) {
      return '<div class="text-gray-500 italic">No documents uploaded.</div>';
    }

    $media = $collection === 'signed_contracts'
      ? [$this->record->getFirstMedia($collection)]
      : $this->record->getMedia($collection);

    return View::make('filament.components.document-list', [
      'media' => $media,
      'getFileTypeIcon' => [$this, 'getFileTypeIcon'],
      'renderPreviewButton' => [$this, 'renderPreviewButton'],
    ])->render();
  }

  protected function getFileTypeIcon(string $mimeType): string
  {
    $icon = match (true) {
      str_starts_with($mimeType, 'image/') => <<<'HTML'
                <svg class="w-8 h-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
HTML,
      $mimeType === 'application/pdf' => <<<'HTML'
                <svg class="w-8 h-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
HTML,
      default => <<<'HTML'
                <svg class="w-8 h-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
HTML,
    };

    return '<div class="flex-shrink-0">' . $icon . '</div>';
  }

  protected function renderPreviewButton($mediaItem): string
  {
    if (!in_array($mediaItem->mime_type, ['application/pdf']) &&
      !str_starts_with($mediaItem->mime_type, 'image/')) {
      return '';
    }

    return <<<HTML
            <a href="{$mediaItem->getUrl()}"
               target="_blank"
               class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-4 focus:ring-gray-200">
                Preview
                <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </a>
HTML;
  }
}
