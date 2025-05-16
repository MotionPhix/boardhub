<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewClient extends ViewRecord
{
  protected static string $resource = ClientResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Grid::make(2)
          ->schema([
            Section::make('Basic Information')
              ->schema([
                TextEntry::make('name')
                  ->label('Client Name'),

                TextEntry::make('email')
                  ->label('Email Address'),

                TextEntry::make('phone')
                  ->label('Phone Number'),

                TextEntry::make('company')
                  ->label('Company Name'),

                TextEntry::make('address')
                  ->columnSpanFull(),

                TextEntry::make('created_at')
                  ->dateTime()
                  ->label('Client Since'),
              ]),

            Section::make('Contract Summary')
              ->schema([
                TextEntry::make('contracts_count')
                  ->label('Total Contracts')
                  ->state(fn ($record) => $record->contracts()->count()),

                TextEntry::make('active_contracts_count')
                  ->label('Active Contracts')
                  ->state(fn ($record) => $record->contracts()
                    ->where('agreement_status', 'active')
                    ->where('end_date', '>=', now())
                    ->whereHas('billboards', function ($query) {
                      $query->whereHas('contracts', function ($query) {
                        $query->where('billboard_contract.booking_status', 'in_use');
                      });
                    })
                    ->count()),
              ]),
          ]),

        Section::make('Documents')
          ->schema([
            Grid::make()
              ->schema([
                TextEntry::make('media.name')
                  ->label('File Name'),

                TextEntry::make('media.size')
                  ->label('File Size')
                  ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),

                TextEntry::make('media.created_at')
                  ->label('Upload Date')
                  ->dateTime(),
              ])
              ->columns(3),
          ])
          ->collapsible(),

        Section::make('Recent Contracts')
          ->schema([
            Grid::make()
              ->schema([
                TextEntry::make('contracts')
                  ->listWithLineBreaks()
                  ->limitList(5)
                  ->formatStateUsing(function ($state) {
                    return collect($state)->map(function ($contract) {
                      return [
                        "Contract #{$contract->contract_number}",
                        "Amount: " . money($contract->total_amount),
                        "Status: {$contract->agreement_status}",
                        "Billboards: " . $contract->billboards->count(),
                      ];
                    });
                  }),
              ])
              ->columns(1),
          ])
          ->collapsible(),
      ]);
  }
}
