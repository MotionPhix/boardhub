<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class PreviewContract extends Page
{
  protected static string $resource = ContractResource::class;

  protected static string $view = 'filament.pages.preview-contract';

  public $contract;
  public $previewMode = 'web'; // web or pdf
  public $selectedVersion;

  public function mount($record): void
  {
    $this->contract = static::getResource()::resolveRecordRouteBinding($record);
    $this->selectedVersion = $this->contract->latestVersion()?->id;
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('download_pdf')
        ->label('Download PDF')
        ->icon('heroicon-o-document-arrow-down')
        ->action(fn () => response()->streamDownload(
          fn () => print($this->contract->generatePdf()),
          "{$this->contract->contract_number}.pdf"
        )),

      Action::make('email_contract')
        ->label('Email to Client')
        ->icon('heroicon-o-envelope')
        ->action(fn () => $this->contract->emailToClient())
        ->requiresConfirmation(),

      Action::make('sign_contract')
        ->label('Sign Contract')
        ->icon('heroicon-o-pencil-square')
        ->action('openSignatureModal')
        ->visible(fn () => !$this->contract->signed_at),
    ];
  }

  protected function getViewData(): array
  {
    return [
      'versions' => $this->contract->versions()->get(),
      'currentVersion' => ContractVersion::find($this->selectedVersion),
    ];
  }
}
