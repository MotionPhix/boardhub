<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use App\Models\Settings;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSettings extends EditRecord
{
  protected static string $resource = SettingsResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }

  public function mount(int|string $record): void
  {
    $this->record = Settings::instance();
    $this->form->fill($this->record->attributesToArray());
  }

  protected function getRedirectUrl(): ?string
  {
    return null;
  }

  protected function getSavedNotificationTitle(): ?string
  {
    return 'Settings updated successfully';
  }
}
