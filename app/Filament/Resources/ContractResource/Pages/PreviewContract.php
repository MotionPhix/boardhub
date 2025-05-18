<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

class PreviewContract extends Page
{
  protected static string $resource = ContractResource::class;

  protected static string $view = 'filament.pages.contracts.preview-contract';

  public ?Contract $record = null;
  public string $previewMode = 'web';
  public ?string $signature = null;

  protected function getLayoutData(): array
  {
    return [
      'breadcrumbs' => $this->getBreadcrumbs(),
      'navigationItems' => [],
      'sidebarFullWidth' => true,
    ];
  }

  public function mount(Contract $record): void
  {
    $this->record = $record->load(['client', 'currency', 'billboards.location']);

    static::authorizeResourceAccess();

    if ($this->record->signature) {
      // Ensure signed document exists
      if (!Storage::disk(config('sign-pad.disk'))->exists($this->record->signature->getSignedDocumentPath())) {
        // Regenerate if missing
        $this->record->signature->generateSignedDocument();
      }
    }
  }

  public function getTitle(): string|Htmlable
  {
    return "Contract Preview: {$this->record->contract_number}";
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('download_pdf')
        ->label('Download PDF')
        ->icon('heroicon-o-document-arrow-down')
        ->url(fn () => $this->record->signature?->getSignedDocumentPath())
        ->openUrlInNewTab()
        ->visible(fn () => $this->record->hasBeenSigned())
        ->color('success'),

      Action::make('sign')
        ->label('Sign Contract')
        ->modalHeading('Sign Contract')
        ->modalSubmitActionLabel('Save Signature')
        ->visible(fn () => !$this->record->hasBeenSigned())
        ->action(fn () => $this->openSignatureModal())
        ->modalWidth('md'),

      Action::make('delete_signature')
        ->label('Delete Signature')
        ->icon('heroicon-o-trash')
        ->color('danger')
        ->visible(fn () => $this->record->hasBeenSigned())
        ->requiresConfirmation()
        ->modalDescription('Are you sure you want to delete the signature? This cannot be undone.')
        ->action(fn () => $this->deleteSignature()),

      Action::make('send')
        ->label('Send to Client')
        ->icon('heroicon-m-paper-airplane')
        ->requiresConfirmation()
        ->modalHeading('Send Contract to Client')
        ->modalDescription('Are you sure you want to send this contract to ' . $this->record->client->name . '?')
        ->modalSubmitActionLabel('Yes, send it')
        ->action('sendToClient')
        ->visible(fn () => $this->record->hasBeenSigned()),
    ];
  }

  protected function openSignatureModal(): void
  {
    $this->dispatch('open-modal', id: 'signatureModal');
  }

  protected function deleteSignature(): void
  {
    $this->record->deleteSignature();

    Notification::make()
      ->title('Success')
      ->body('Signature deleted successfully.')
      ->success()
      ->send();

    $this->redirect($this->getResource()::getUrl('preview', ['record' => $this->record]));
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
