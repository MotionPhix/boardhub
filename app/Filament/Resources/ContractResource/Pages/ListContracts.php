<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContracts extends ListRecords
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\CreateAction::make(),
    ];
  }

  public function getTabs(): array
  {
    return [
      'all' => Tab::make('All Contracts')
        ->badge($this->getContractCount()),
      'active' => Tab::make('Active')
        ->badge($this->getContractCount('active'))
        ->modifyQueryUsing(fn (Builder $query) => $query
          ->where('agreement_status', 'active')
          ->whereHas('billboards', function ($query) {
            $query->wherePivot('booking_status', 'in_use');
          })),
      'draft' => Tab::make('Draft')
        ->badge($this->getContractCount('draft'))
        ->modifyQueryUsing(fn (Builder $query) => $query->where('agreement_status', 'draft')),
      'completed' => Tab::make('Completed')
        ->badge($this->getContractCount('completed'))
        ->modifyQueryUsing(fn (Builder $query) => $query->where('agreement_status', 'completed')),
      'cancelled' => Tab::make('Cancelled')
        ->badge($this->getContractCount('cancelled'))
        ->modifyQueryUsing(fn (Builder $query) => $query->where('agreement_status', 'cancelled')),
    ];
  }

  private function getContractCount(?string $status = null): int
  {
    $query = static::getModel()::query();

    if ($status) {
      $query->where('agreement_status', $status);
      if ($status === 'active') {
        $query->whereHas('billboards', function ($query) {
          $query->wherePivot('booking_status', 'in_use');
        });
      }
    }

    return $query->count();
  }
}
