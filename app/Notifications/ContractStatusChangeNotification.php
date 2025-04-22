<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractStatusChangeNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract,
    protected string $oldStatus,
    protected string $newStatus
  ) {}

  public function via($notifiable): array
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject("Contract Status Changed - {$this->contract->contract_number}")
      ->greeting("Hello {$notifiable->name},")
      ->line("A contract status has been updated:")
      ->line(new HtmlString("<strong>Contract Details:</strong>"))
      ->line("Contract Number: {$this->contract->contract_number}")
      ->line("Client: {$this->contract->client->name}")
      ->line("Previous Status: " . ucfirst($this->oldStatus))
      ->line("New Status: " . ucfirst($this->newStatus))
      ->line("Total Amount: MK " . number_format($this->contract->total_amount, 2))
      ->action('View Contract', route('filament.admin.resources.contracts.view', $this->contract));
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'old_status' => $this->oldStatus,
      'new_status' => $this->newStatus,
      'action' => 'status_changed',
    ];
  }
}
