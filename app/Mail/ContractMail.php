<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractMail extends Mailable
{
  use Queueable, SerializesModels;

  public string $viewUrl;

  public function __construct(
    public Contract $contract,
    public Media $contractFile
  ) {
    $this->viewUrl = route('filament.resources.contracts.view', ['record' => $contract->id]);
  }

  public function build()
  {
    return $this->markdown('emails.contract-mail')
      ->subject("Contract #{$this->contract->contract_number} - Ready for Review")
      ->attachFromStorageDisk(
        $this->contractFile->disk,
        $this->contractFile->getPath(),
        $this->contract->contract_number . '.pdf'
      );
  }
}
