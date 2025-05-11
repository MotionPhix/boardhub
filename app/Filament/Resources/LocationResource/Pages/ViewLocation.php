<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Settings;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Number;
use Filament\Support\Enums\IconPosition;

class ViewLocation extends ViewRecord
{
  protected static string $resource = LocationResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->icon('heroicon-m-pencil-square'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    $defaultCurrency = Settings::get('default_currency.code', 'MWK');

    return $infolist
      ->schema([
        // Location Overview Section
        Infolists\Components\Section::make([
          Infolists\Components\TextEntry::make('name')
            ->weight(FontWeight::Bold)
            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

          Infolists\Components\Grid::make(3)
            ->schema([
              Infolists\Components\TextEntry::make('city')
                ->icon('heroicon-m-building-office-2'),
              Infolists\Components\TextEntry::make('state')
                ->icon('heroicon-m-map'),
              Infolists\Components\TextEntry::make('country')
                ->formatStateUsing(fn ($record) => $record->getCountryName())
                ->icon('heroicon-m-globe-alt'),
            ]),

          // Record History Section
          Infolists\Components\Section::make('Record History')
            ->icon('heroicon-m-clock')
            ->schema([
              Infolists\Components\Grid::make(2)
                ->schema([
                  Infolists\Components\TextEntry::make('created_at')
                    ->dateTime()
                    ->label('Added On')
                    ->icon('heroicon-m-calendar')
                    ->color('gray'),

                  Infolists\Components\TextEntry::make('updated_at')
                    ->dateTime()
                    ->label('Last Updated')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray'),
                ]),
            ])
            ->collapsible()
            ->columnSpan('full'),
        ])
          ->columnSpan(['lg' => 2]),

        // Quick Stats Section
        Infolists\Components\Section::make('Statistics')
          ->description('Current billboard statistics')
          ->icon('heroicon-m-chart-bar')
          ->schema([
            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('total_billboards')
                  ->label('Total Billboards')
                  ->state(function($record) {
                    $total = $record->billboards()->count();
                    return Number::format($total);
                  })
                  ->icon('heroicon-m-rectangle-stack')
                  ->color('gray'),

                Infolists\Components\TextEntry::make('active_billboards')
                  ->label('Active Billboards')
                  ->state(function($record) {
                    $active = $record->billboards()
                      ->whereHas('contracts', function ($query) {
                        $query->where('booking_status', 'in_use');
                      })->count();
                    $total = $record->billboards()->count();

                    return Number::format($active) . ' (' .
                      ($total ? round(($active / $total) * 100) : 0) . '%)';
                  })
                  ->icon('heroicon-m-check-circle')
                  ->color('success'),

                Infolists\Components\TextEntry::make('maintenance_billboards')
                  ->label('Under Maintenance')
                  ->state(function($record) {
                    $maintenance = $record->billboards()
                      ->where('physical_status', 'maintenance')->count();
                    $total = $record->billboards()->count();

                    return Number::format($maintenance) . ' (' .
                      ($total ? round(($maintenance / $total) * 100) : 0) . '%)';
                  })
                  ->icon('heroicon-m-wrench-screwdriver')
                  ->color('warning'),

                Infolists\Components\TextEntry::make('damaged_billboards')
                  ->label('Damaged')
                  ->state(function($record) {
                    $damaged = $record->billboards()
                      ->where('physical_status', 'damaged')->count();
                    $total = $record->billboards()->count();

                    return Number::format($damaged) . ' (' .
                      ($total ? round(($damaged / $total) * 100) : 0) . '%)';
                  })
                  ->icon('heroicon-m-exclamation-triangle')
                  ->color('danger'),
              ]),
          ])
          ->collapsible()
          ->columnSpan(['lg' => 1]),

        // Billboards List Section
        Infolists\Components\Section::make('Billboards')
          ->description('List of all billboards in this location')
          ->icon('heroicon-m-rectangle-stack')
          ->schema([
            Infolists\Components\RepeatableEntry::make('billboards')
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->weight(FontWeight::Bold)
                  ->copyable()
                  ->copyMessage('Billboard name copied!')
                  ->copyMessageDuration(1500),

                Infolists\Components\TextEntry::make('type')
                  ->badge()
                  ->color(fn (string $state): string => match ($state) {
                    'static' => 'gray',
                    'digital' => 'success',
                    'mobile' => 'warning',
                    default => 'gray',
                  }),

                Infolists\Components\TextEntry::make('size')
                  ->icon('heroicon-m-arrows-pointing-out')
                  ->iconPosition(IconPosition::After),

                Infolists\Components\TextEntry::make('base_price')
                  ->label('Monthly Price')
                  ->money(fn ($record) => $record->currency_code ?? $defaultCurrency)
                  ->icon('heroicon-m-currency-dollar'),

                Infolists\Components\TextEntry::make('physical_status')
                  ->badge()
                  ->icon(fn(string $state): string => match ($state) {
                    'operational' => 'heroicon-m-check-circle',
                    'maintenance' => 'heroicon-m-wrench-screwdriver',
                    'damaged' => 'heroicon-m-exclamation-triangle',
                    default => 'heroicon-m-question-mark-circle',
                  })
                  ->color(fn(string $state): string => match ($state) {
                    'operational' => 'success',
                    'maintenance' => 'warning',
                    'damaged' => 'danger',
                    default => 'gray',
                  }),
              ])
              ->columns(5)
              ->contained(true),
          ])
          ->collapsible()
          ->columnSpan('full')
          ->hidden(fn($record) => $record->billboards()->count() === 0),
      ])
      ->columns(3);
  }
}
