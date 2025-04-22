<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractActivationNotification extends Notification implements ShouldQueue
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
    $billboardsList = $this->contract->billboards
      ->map(fn($billboard) => "- {$billboard->name} ({$billboard->location})")
      ->join("\n");

    return (new MailMessage)
      ->subject("Contract Activated - {$this->contract->contract_number}")
      ->greeting("Hello {$notifiable->name},")
      ->line("A contract has been activated:")
      ->line(new HtmlString("<strong>Contract Details:</strong>"))
      ->line("Contract Number: {$this->contract->contract_number}")
      ->line("Client: {$this->contract->client->name}")
      ->line("Total Amount: MK " . number_format($this->contract->total_amount, 2))
      ->line(new HtmlString("<strong>Billboards:</strong>"))
      ->line(new HtmlString("<pre>{$billboardsList}</pre>"))
      ->action('View Contract', route('filament.admin.resources.contracts.view', $this->contract));
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'total_amount' => $this->contract->total_amount,
      'billboards_count' => $this->contract->billboards->count(),
      'action' => 'activated',
    ];
  }
}
