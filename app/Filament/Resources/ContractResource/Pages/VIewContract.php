<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;

class ViewContract extends ViewRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      Actions\Action::make('download')
        ->icon('heroicon-o-document-arrow-down')
        ->label('Download Contract')
        ->visible(fn () => $this->record->hasMedia('signed_contracts'))
        ->action(fn () => redirect($this->record->getFirstMediaUrl('signed_contracts'))),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Grid::make(3)
          ->schema([
            Section::make('Contract Information')
              ->schema([
                TextEntry::make('contract_number')
                  ->label('Contract Number'),
                TextEntry::make('client.name')
                  ->label('Client'),
                TextEntry::make('total_amount')
                  ->money()
                  ->label('Contract Value'),
                TextEntry::make('agreement_status')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'draft' => 'warning',
                    'completed' => 'gray',
                    'cancelled' => 'danger',
                  }),
                TextEntry::make('created_at')
                  ->dateTime()
                  ->label('Created'),
                TextEntry::make('notes')
                  ->markdown()
                  ->columnSpanFull(),
              ])
              ->columnSpan(2),

            Section::make('Client Details')
              ->schema([
                TextEntry::make('client.email')
                  ->label('Email'),
                TextEntry::make('client.phone')
                  ->label('Phone'),
                TextEntry::make('client.company')
                  ->label('Company'),
              ])
              ->columnSpan(1),
          ]),

        Section::make('Billboards')
          ->schema([
            RepeatableEntry::make('billboards')
              ->schema([
                TextEntry::make('name')
                  ->label('Billboard'),
                TextEntry::make('location')
                  ->label('Location'),
                TextEntry::make('physical_status')
                  ->badge(),
                TextEntry::make('pivot.booking_status')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'in_use' => 'success',
                    'pending' => 'warning',
                    'completed' => 'gray',
                    'cancelled' => 'danger',
                    default => 'gray',
                  }),
                TextEntry::make('pivot.price')
                  ->money()
                  ->label('Price'),
              ])
              ->columns(5),
          ]),

        Section::make('Documents')
          ->schema([
            SpatieMediaLibraryImageEntry::make('contract_documents')
              ->label('Contract Documents')
              ->collection('contract_documents')
              ->columnSpanFull(),
            SpatieMediaLibraryImageEntry::make('signed_contract')
              ->label('Signed Contract')
              ->collection('signed_contracts')
              ->columnSpanFull(),
          ])
          ->collapsible(),
      ]);
  }
}
