<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewLocation extends ViewRecord
{
  protected static string $resource = LocationResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->icon('heroicon-m-pencil-square'),
      Actions\Action::make('view_map')
        ->icon('heroicon-m-map')
        ->url(fn () => "https://www.openstreetmap.org/?mlat={$this->record->latitude}&mlon={$this->record->longitude}&zoom=15")
        ->openUrlInNewTab(),
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

            Infolists\Components\TextEntry::make('postal_code')
              ->label('Postal Code'),
          ])
          ->columnSpan(['lg' => 2]),

        Infolists\Components\Section::make('Statistics')
          ->schema([
            Infolists\Components\TextEntry::make('billboards_count')
              ->label('Total Billboards')
              ->state(fn ($record) => $record->billboards()->count())
              ->color('gray'),

            Infolists\Components\TextEntry::make('active_billboards')
              ->label('Active Billboards')
              ->state(fn ($record) => $record->billboards()
                ->whereHas('contracts', function ($query) {
                  $query->where('billboard_contract.booking_status', 'in_use');
                })->count())
              ->color('success'),

            Infolists\Components\TextEntry::make('maintenance_billboards')
              ->label('Under Maintenance')
              ->state(fn ($record) => $record->billboards()
                ->where('physical_status', 'maintenance')->count())
              ->color('warning'),

            Infolists\Components\TextEntry::make('damaged_billboards')
              ->label('Damaged Billboards')
              ->state(fn ($record) => $record->billboards()
                ->where('physical_status', 'damaged')->count())
              ->color('danger'),
          ])
          ->columnSpan(['lg' => 1]),

        Infolists\Components\Section::make('Map Location')
          ->schema([
            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('latitude')
                  ->numeric(6),
                Infolists\Components\TextEntry::make('longitude')
                  ->numeric(6),
              ]),
            Infolists\Components\View::make('filament.infolists.components.location-map'),
          ])
          ->columnSpan('full'),

        Infolists\Components\Section::make('Billboards')
          ->schema([
            Infolists\Components\RepeatableEntry::make('billboards')
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->weight(FontWeight::Bold),
                Infolists\Components\TextEntry::make('type'),
                Infolists\Components\TextEntry::make('size'),
                Infolists\Components\TextEntry::make('price')
                  ->money(),
                Infolists\Components\TextEntry::make('physical_status')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'operational' => 'success',
                    'maintenance' => 'warning',
                    'damaged' => 'danger',
                    default => 'gray',
                  }),
                Infolists\Components\TextEntry::make('current_contract.contract_number')
                  ->label('Active Contract')
                  ->placeholder('-'),
              ])
              ->columns(6),
          ])
          ->columnSpan('full')
          ->hidden(fn ($record) => $record->billboards()->count() === 0),

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
