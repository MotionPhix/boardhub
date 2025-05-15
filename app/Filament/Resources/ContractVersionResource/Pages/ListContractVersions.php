<?php

namespace App\Filament\Resources\ContractVersionResource\Pages;

use App\Filament\Resources\ContractVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractVersions extends ListRecords
{
    protected static string $resource = ContractVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
