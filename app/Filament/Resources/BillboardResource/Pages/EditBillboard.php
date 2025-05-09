<?php

namespace App\Filament\Resources\BillboardResource\Pages;

use App\Filament\Resources\BillboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillboard extends EditRecord
{
  protected static string $resource = BillboardResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\ViewAction::make(),
      Actions\DeleteAction::make(),
      Actions\Action::make('viewOnMap')
        ->url("https://www.google.com/maps?q={$this->record->latitude},{$this->record->longitude}")
        ->icon('heroicon-o-map-pin')
        ->visible(fn() => $this->record->latitude && $this->record->longitude)
        ->openUrlInNewTab(),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      BillboardResource\Widgets\BillboardStats::class,
    ];
  }
}
