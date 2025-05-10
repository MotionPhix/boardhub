<?php

namespace App\Filament\Resources\BillboardResource\Pages;

use App\Filament\Resources\BillboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewBillboard extends ViewRecord
{
  protected static string $resource = BillboardResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('viewOnMap')
        ->url("https://www.google.com/maps?q={$this->record->latitude},{$this->record->longitude}")
        ->icon('heroicon-o-map-pin')
        ->visible(fn () => $this->record->latitude && $this->record->longitude)
        ->openUrlInNewTab(),
      Actions\EditAction::make(),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      BillboardResource\Widgets\BillboardStats::class,
    ];
  }

  protected function getAdditionalInformationSchema(): array
  {
    return [
      Section::make('Current Contract Details')
        ->schema([
          Grid::make(2)->schema([
            TextEntry::make('current_contract.contract_number')
              ->label('Contract Number'),
            TextEntry::make('current_contract.client.name')
              ->label('Client'),
            TextEntry::make('current_contract.start_date')
              ->label('Start Date')
              ->date(),
            TextEntry::make('current_contract.end_date')
              ->label('End Date')
              ->date(),
            TextEntry::make('current_contract.base_price')
              ->label('Base Price')
              ->money(),
            TextEntry::make('current_contract.final_price')
              ->label('Final Price')
              ->money(),
          ])
        ])
        ->visible(fn () => $this->record->current_contract !== null)
    ];
  }
}
