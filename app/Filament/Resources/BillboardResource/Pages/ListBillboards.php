<?php

namespace App\Filament\Resources\BillboardResource\Pages;

use App\Filament\Resources\BillboardResource;
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
      'available' => Tab::make('Available')
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('status', 'Available')
          ->where(function ($query) {
            $query->whereNull('available_until')
              ->orWhere('available_until', '>', now());
          }))
        ->badge($this->getModel()::where('status', 'Available')->count())
        ->badgeColor('success'),
      'occupied' => Tab::make('Occupied')
        ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Occupied'))
        ->badge($this->getModel()::where('status', 'Occupied')->count())
        ->badgeColor('warning'),
      'maintenance' => Tab::make('In Maintenance')
        ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Maintenance'))
        ->badge($this->getModel()::where('status', 'Maintenance')->count())
        ->badgeColor('danger'),
    ];
  }
}
