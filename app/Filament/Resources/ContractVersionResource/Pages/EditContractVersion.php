<?php

namespace App\Filament\Resources\ContractVersionResource\Pages;

use App\Filament\Resources\ContractVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractVersion extends EditRecord
{
    protected static string $resource = ContractVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
