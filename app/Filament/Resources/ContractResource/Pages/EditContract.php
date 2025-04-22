<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditContract extends EditRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\ViewAction::make(),
      Actions\Action::make('download')
        ->icon('heroicon-o-document-arrow-down')
        ->label('Download Contract')
        ->visible(fn () => $this->record->hasMedia('signed_contracts'))
        ->action(fn () => redirect($this->record->getFirstMediaUrl('signed_contracts'))),
      Actions\DeleteAction::make(),
    ];
  }

  protected function afterSave(): void
  {
    // Update booking status for billboards when agreement status changes
    if ($this->record->wasChanged('agreement_status')) {
      $bookingStatus = match ($this->record->agreement_status) {
        'active' => BookingStatus::IN_USE,
        'completed' => BookingStatus::COMPLETED,
        'cancelled' => BookingStatus::CANCELLED,
        default => BookingStatus::PENDING,
      };

      foreach ($this->record->billboards as $billboard) {
        $billboard->pivot->update([
          'booking_status' => $bookingStatus->value,
        ]);
      }
    }
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('view', ['record' => $this->record]);
  }
}
