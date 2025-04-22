<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateContract extends CreateRecord
{
  protected static string $resource = ContractResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    // Generate contract number if not set
    if (!isset($data['contract_number'])) {
      $data['contract_number'] = 'CNT-' . date('Y') . '-' . str_pad((static::getModel()::count() + 1), 5, '0', STR_PAD_LEFT);
    }

    return $data;
  }

  protected function afterCreate(): void
  {
    // Set the initial booking status for each billboard
    $contract = $this->record;
    $bookingStatus = $contract->agreement_status === 'active'
      ? BookingStatus::IN_USE
      : BookingStatus::PENDING;

    foreach ($contract->billboards as $billboard) {
      $billboard->pivot->update([
        'booking_status' => $bookingStatus->value,
      ]);
    }
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('view', ['record' => $this->record]);
  }
}
