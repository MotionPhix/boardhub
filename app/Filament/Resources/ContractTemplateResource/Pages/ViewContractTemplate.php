<?php

namespace App\Filament\Resources\ContractTemplateResource\Pages;

use App\Filament\Resources\ContractTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Jobs\GenerateContractTemplatePreview;
use Filament\Notifications\Notification;

class ViewContractTemplate extends ViewRecord
{
  protected static string $resource = ContractTemplateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('preview')
        ->label('Generate Preview')
        ->icon('heroicon-o-eye')
        ->action(function () {
          GenerateContractTemplatePreview::dispatch($this->record);

          Notification::make()
            ->title('Preview generation started')
            ->success()
            ->send();
        }),

      Actions\EditAction::make()
        ->icon('heroicon-o-pencil'),

      Actions\DeleteAction::make()
        ->icon('heroicon-o-trash'),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      ContractTemplateResource\Widgets\ContractTemplatePreview::class,
    ];
  }
}
