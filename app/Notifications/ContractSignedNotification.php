<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignedNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract
  ) {}

  public function via($notifiable): array
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject("Contract {$this->contract->contract_number} has been signed")
      ->greeting("Hello {$notifiable->name},")
      ->line("The contract for {$this->contract->client->name} has been signed.")
      ->line('Contract Details:')
      ->line("Contract Number: {$this->contract->contract_number}")
      ->line("Client: {$this->contract->client->name}")
      ->line("Amount: {$this->contract->currency_code} " .
        number_format($this->contract->contract_final_amount, 2))
      ->action('View Contract', route('filament.admin.resources.contracts.view', $this->contract))
      ->line('The signed contract has been stored in the system.');
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'signed_at' => $this->contract->signed_at->toDateTimeString(),
      'action' => 'signed',
    ];
  }
}
