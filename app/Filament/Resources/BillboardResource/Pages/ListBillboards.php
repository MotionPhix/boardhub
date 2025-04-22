<?php

namespace App\Filament\Resources\BillboardResource\Pages;

use App\Filament\Resources\BillboardResource;
use App\Models\Billboard;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBillboards extends ListRecords
{
  protected static string $resource = BillboardResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\CreateAction::make(),
      Actions\Action::make('export')
        ->icon('heroicon-o-arrow-down-tray')
        ->action(function () {
          // Add export logic here if needed
        }),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      BillboardResource\Widgets\BillboardOverview::class,
    ];
  }

  public function getTabs(): array
  {
    return [
      'all' => Tab::make('All Billboards')
        ->badge($this->getModel()::count()),

      'operational' => Tab::make('Operational')
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL))
        ->badge($this->getModel()::where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)->count())
        ->badgeColor('success'),

      'available' => Tab::make('Available')
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)
          ->whereDoesntHave('contracts', function ($query) {
            $query->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now());
          }))
        ->badge($this->getModel()::where('physical_status', Billboard::PHYSICAL_STATUS_OPERATIONAL)
          ->whereDoesntHave('contracts', function ($query) {
            $query->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now());
          })->count())
        ->badgeColor('success'),

      'maintenance' => Tab::make('Under Maintenance')
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('physical_status', Billboard::PHYSICAL_STATUS_MAINTENANCE))
        ->badge($this->getModel()::where('physical_status', Billboard::PHYSICAL_STATUS_MAINTENANCE)->count())
        ->badgeColor('warning'),

      'damaged' => Tab::make('Damaged')
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('physical_status', Billboard::PHYSICAL_STATUS_DAMAGED))
        ->badge($this->getModel()::where('physical_status', Billboard::PHYSICAL_STATUS_DAMAGED)->count())
        ->badgeColor('danger'),
    ];
  }
}
