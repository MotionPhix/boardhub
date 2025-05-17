<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class PreviewContract extends Page
{
  protected static string $resource = ContractResource::class;

  protected static string $view = 'filament.pages.contracts.preview-contract';

  public ?Contract $record = null;

  public function mount(Contract $record): void
  {
    $this->record = $record;
  }

  public function getTitle(): string|Htmlable
  {
    return "Preview Contract: {$this->record->contract_number}";
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('download')
        ->label('Download PDF')
        ->icon('heroicon-m-arrow-down-tray')
        ->action(function () {
          try {
            $pdf = $this->record->generatePdf();

            return response()->streamDownload(
              fn () => print($pdf),
              "{$this->record->contract_number}.pdf",
              ['Content-Type' => 'application/pdf']
            );
          } catch (\Exception $e) {
            throw new Halt($e->getMessage());
          }
        }),

      Action::make('send')
        ->label('Send to Client')
        ->icon('heroicon-m-paper-airplane')
        ->requiresConfirmation()
        ->modalHeading('Send Contract to Client')
        ->modalDescription('Are you sure you want to send this contract to ' . $this->record->client->name . '?')
        ->modalSubmitActionLabel('Yes, send it')
        ->action(function () {
          try {
            $this->record->emailToClient();

            $this->notify('success', 'Contract sent to client successfully.');
          } catch (\Exception $e) {
            throw new Halt($e->getMessage());
          }
        }),
    ];
  }

  public function generatePdf(): void
  {
    try {
      $pdf = $this->record->generatePdf();

      $this->record
        ->addMediaFromString($pdf)
        ->usingFileName("{$this->record->contract_number}.pdf")
        ->withCustomProperties([
          'generated_by' => auth()->user()->name,
          'generated_at' => now()->toDateTimeString(),
        ])
        ->toMediaCollection('contract_documents');

      $this->notify('success', 'Contract PDF generated successfully.');

      $this->redirect(url()->current());
    } catch (\Exception $e) {
      throw new Halt($e->getMessage());
    }
  }
}
