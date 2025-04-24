<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
  protected static string $resource = UserResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\ViewAction::make(),
      Actions\DeleteAction::make(),
      Actions\ForceDeleteAction::make(),
      Actions\RestoreAction::make(),
    ];
  }

  protected function handleRecordUpdate(Model $record, array $data): Model
  {
    if (empty($data['password'])) {
      unset($data['password']);
    }

    $record->update($data);
    return $record;
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('index');
  }
}
