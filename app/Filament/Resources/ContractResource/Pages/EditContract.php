<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditContract extends EditRecord
{
  protected static string $resource = ContractResource::class;
  protected array $changes = [];

  protected function authorizeAccess(): void
  {
    parent::authorizeAccess();

    $record = $this->getRecord();

    if ($record->agreement_status !== 'draft') {

      Notification::make()
        ->title('No edits allowed')
        ->danger()
        ->body('This contract cannot be edited as it is no longer in draft status.')
        ->send();

      redirect()->to(static::$resource::getUrl('view', ['record' => $record]));
    }
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\ViewAction::make(),
      Actions\DeleteAction::make()
        ->visible(fn () => $this->record->agreement_status === 'draft'),
    ];
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    // Store only what's changed
    $this->changes = collect($data)->diffAssoc($this->record->getAttributes())->all();
    return $data;
  }

  protected function afterSave(): void
  {
    // Only process if we have relevant changes
    if (empty($this->changes)) {
      return;
    }

    DB::transaction(function () {
      // Update booking status if needed
      if (isset($this->changes['agreement_status'])) {
        $bookingStatus = match ($this->record->agreement_status) {
          'active' => BookingStatus::IN_USE,
          'completed' => BookingStatus::COMPLETED,
          'cancelled' => BookingStatus::CANCELLED,
          default => BookingStatus::PENDING,
        };

        // Update in chunks
        $billboardIds = $this->record->billboards()
          ->pluck('billboards.id')
          ->chunk(100);

        foreach ($billboardIds as $chunk) {
          DB::table('billboard_contract')
            ->where('contract_id', $this->record->id)
            ->whereIn('billboard_id', $chunk->toArray())
            ->update(['booking_status' => $bookingStatus->value]);
        }
      }

      // Update contract totals if needed
      if (isset($this->changes['contract_discount']) || isset($this->changes['billboards'])) {
        // Calculate totals
        $totals = DB::table('billboard_contract')
          ->where('contract_id', $this->record->id)
          ->selectRaw('
                        SUM(billboard_base_price) as total_base,
                        COUNT(*) as billboard_count
                    ')
          ->first();

        $discount = $this->record->contract_discount ?? 0;
        $discountPerBillboard = $totals->billboard_count > 0
          ? ($discount / $totals->billboard_count)
          : 0;

        // Update contract totals
        $this->record->update([
          'contract_total' => $totals->total_base,
          'contract_final_amount' => $totals->total_base - $discount,
        ]);

        // Update billboard discounts
        DB::table('billboard_contract')
          ->where('contract_id', $this->record->id)
          ->update([
            'billboard_discount_amount' => $discountPerBillboard,
            'billboard_final_price' => DB::raw("billboard_base_price - $discountPerBillboard")
          ]);
      }
    });
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('view', ['record' => $this->record]);
  }
}
