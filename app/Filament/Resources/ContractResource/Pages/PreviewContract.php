<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractVersion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class PreviewContract extends Page
{
  protected static string $resource = ContractResource::class;

  protected static string $view = 'filament.pages.contracts.preview-contract';

  public ?Contract $record = null;
  public string $previewMode = 'web';
  public ?string $selectedVersion = null;
  public ?string $signature = null;

  public function mount(Contract $record): void
  {
    $this->record = $record->load(['client', 'currency', 'billboards.location', 'versions']);
    $this->selectedVersion = $record->versions()->latest()->first()?->id;
  }

  public function getTitle(): string|Htmlable
  {
    return "Contract Preview: {$this->record->contract_number}";
  }

  public function getCurrentVersionProperty(): ?ContractVersion
  {
    return $this->versions->find($this->selectedVersion);
  }

  public function getVersionsProperty(): Collection
  {
    return $this->record->versions()->latest()->get();
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('download')
        ->label('Download PDF')
        ->icon('heroicon-m-arrow-down-tray')
        ->action('downloadPdf')
        ->visible(fn () => $this->record->hasMedia('contract_documents')),

      Action::make('sign')
        ->label('Sign Contract')
        ->icon('heroicon-m-pencil-square')
        ->action(fn () => $this->dispatch('openModal', id: 'signatureModal'))
        ->visible(fn () => !$this->record->signed_at),

      Action::make('send')
        ->label('Send to Client')
        ->icon('heroicon-m-paper-airplane')
        ->requiresConfirmation()
        ->modalHeading('Send Contract to Client')
        ->modalDescription('Are you sure you want to send this contract to ' . $this->record->client->name . '?')
        ->modalSubmitActionLabel('Yes, send it')
        ->action('sendToClient')
        ->visible(fn () => $this->record->hasMedia('contract_documents')),
    ];
  }

  public function clearSignature(): void
  {
    $this->dispatch('clear-signature');
  }

  public function saveSignature(): void
  {
    try {
      if (empty($this->signature)) {
        throw new \Exception('Please provide a signature.');
      }

      $this->record->addSignature('company', $this->signature);

      $this->dispatch('closeModal', id: 'signatureModal');

      Notification::make()
        ->title('Success')
        ->body('Contract signed successfully.')
        ->success()
        ->send();

      $this->signature = null;

    } catch (\Exception $e) {
      Notification::make()
        ->title('Error')
        ->body($e->getMessage())
        ->danger()
        ->send();

      throw new Halt();
    }
  }

  public function downloadPdf()
  {
    try {
      if (!$this->record->hasMedia('contract_documents')) {
        throw new \Exception('No PDF document available.');
      }

      $media = $this->record->getFirstMedia('contract_documents');

      return response()->download(
        $media->getPath(),
        "{$this->record->contract_number}.pdf",
        ['Content-Type' => 'application/pdf']
      );

    } catch (\Exception $e) {
      Notification::make()
        ->title('Error')
        ->body($e->getMessage())
        ->danger()
        ->send();

      throw new Halt();
    }
  }

  public function sendToClient(): void
  {
    try {
      $this->record->emailToClient();

      Notification::make()
        ->title('Success')
        ->body('Contract sent to client successfully.')
        ->success()
        ->send();

    } catch (\Exception $e) {
      Notification::make()
        ->title('Error')
        ->body($e->getMessage())
        ->danger()
        ->send();

      throw new Halt();
    }
  }
}
