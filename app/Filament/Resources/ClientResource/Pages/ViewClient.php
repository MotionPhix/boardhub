<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;

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
                    ->whereHas('billboards', function ($query) {
                      $query->wherePivot('booking_status', 'in_use');
                    })
                    ->count()),
                TextEntry::make('total_contracts_value')
                  ->label('Total Contract Value')
                  ->money()
                  ->state(fn ($record) => $record->contracts()
                    ->where('agreement_status', 'active')
                    ->sum('total_amount')),
              ]),
          ]),

        Section::make('Documents')
          ->schema([
            RepeatableEntry::make('media')
              ->label('Uploaded Documents')
              ->schema([
                TextEntry::make('name')
                  ->label('File Name'),
                TextEntry::make('size')
                  ->label('File Size')
                  ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),
                TextEntry::make('created_at')
                  ->label('Upload Date')
                  ->dateTime(),
              ])
              ->columns(3),
          ])
          ->collapsible(),

        Section::make('Recent Contracts')
          ->schema([
            RepeatableEntry::make('contracts')
              ->label(false)
              ->schema([
                TextEntry::make('contract_number')
                  ->label('Contract #'),
                TextEntry::make('total_amount')
                  ->label('Amount')
                  ->money(),
                TextEntry::make('agreement_status')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'draft' => 'warning',
                    'completed' => 'gray',
                    'cancelled' => 'danger',
                  }),
                TextEntry::make('billboards_count')
                  ->label('Billboards')
                  ->state(fn ($record) => $record->billboards()->count()),
              ])
              ->limit(5)
              ->columns(4),
          ])
          ->collapsible(),
      ]);
  }
}
