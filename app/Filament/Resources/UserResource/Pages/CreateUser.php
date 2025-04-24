<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
  protected static string $resource = UserResource::class;

  protected function handleRecordCreation(array $data): Model
  {
    $data['password'] = bcrypt($data['password']);
    return static::getModel()::create($data);
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('index');
  }
}
