<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Dotswan\MapPicker\Infolists\MapEntry;

class ViewLocation extends ViewRecord
{
  protected static string $resource = LocationResource::class;

  public $location;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->icon('heroicon-m-pencil-square'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Location Details')
          ->schema([
            Infolists\Components\TextEntry::make('name')
              ->weight(FontWeight::Bold)
              ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('city'),
                Infolists\Components\TextEntry::make('state'),
                Infolists\Components\TextEntry::make('country'),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Infolists\Components\Section::make('Statistics')
          ->schema([
            Infolists\Components\TextEntry::make('billboards_count')
              ->label('Total Billboards')
              ->state(fn($record) => $record->billboards()->count())
              ->color('gray'),

            Infolists\Components\TextEntry::make('active_billboards')
              ->label('Active Billboards')
              ->state(fn($record) => $record->billboards()
                ->whereHas('contracts', function ($query) {
                  $query->where('billboard_contract.booking_status', 'in_use');
                })->count())
              ->color('success'),

            Infolists\Components\TextEntry::make('maintenance_billboards')
              ->label('Under Maintenance')
              ->state(fn($record) => $record->billboards()
                ->where('physical_status', 'maintenance')->count())
              ->color('warning'),

            Infolists\Components\TextEntry::make('damaged_billboards')
              ->label('Damaged Billboards')
              ->state(fn($record) => $record->billboards()
                ->where('physical_status', 'damaged')->count())
              ->color('danger'),
          ])
          ->columnSpan(['lg' => 1]),

        Infolists\Components\Section::make('Billboards')
          ->schema([
            Infolists\Components\RepeatableEntry::make('billboards')
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->weight(FontWeight::Bold),
                Infolists\Components\TextEntry::make('type'),
                Infolists\Components\TextEntry::make('size'),
                Infolists\Components\TextEntry::make('base_price')
                  ->label('Rental Fee (Monthly)')
                  ->money(),
                Infolists\Components\TextEntry::make('physical_status')
                  ->badge()
                  ->color(fn(string $state): string => match ($state) {
                    'operational' => 'success',
                    'maintenance' => 'warning',
                    'damaged' => 'danger',
                    default => 'gray',
                  }),
              ])
              ->columns(6),
          ])
          ->columnSpan('full')
          ->hidden(fn($record) => $record->billboards()->count() === 0),

        Infolists\Components\Section::make('Record History')
          ->schema([
            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('created_at')
                  ->dateTime()
                  ->label('Created')
                  ->color('gray'),
                Infolists\Components\TextEntry::make('updated_at')
                  ->dateTime()
                  ->label('Last Updated')
                  ->color('gray'),
              ]),
          ])
          ->columnSpan('full'),
      ])
      ->columns(3);
  }
}
