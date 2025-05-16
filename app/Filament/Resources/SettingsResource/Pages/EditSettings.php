<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSettings extends EditRecord
{
  protected static string $resource = SettingsResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make(),
    ];
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    // Ensure we're not losing the existing value structure
    $existingValue = $this->record->value ?? [];
    $newValue = $data['value'] ?? [];

    // Merge the new value with existing value to preserve structure
    $data['value'] = array_merge($existingValue, $newValue);

    return $data;
  }

  protected function afterSave(): void
  {
    Notification::make()
      ->success()
      ->title('Settings updated successfully')
      ->send();
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('index');
  }
}
