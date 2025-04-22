<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContractExpirationNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract,
    protected int $daysActive,
    protected int $threshold
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
      ->subject("Contract Review Required - {$this->contract->contract_number}")
      ->greeting("Hello {$notifiable->name},")
      ->line("A contract requires your attention:")
      ->line(new HtmlString("<strong>Contract Details:</strong>"))
      ->line("Contract Number: {$this->contract->contract_number}")
      ->line("Client: {$this->contract->client->name}")
      ->line("Active Duration: {$this->daysActive} days")
      ->line("Total Amount: MK " . number_format($this->contract->total_amount, 2))
      ->line(new HtmlString("<strong>Billboards:</strong>"))
      ->line(new HtmlString("<pre>{$billboardsList}</pre>"))
      ->action('View Contract', route('filament.admin.resources.contracts.view', $this->contract))
      ->line("Please review the contract status and contact the client if necessary.")
      ->line("Thank you for using our application!");
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'days_active' => $this->daysActive,
      'threshold' => $this->threshold,
      'total_amount' => $this->contract->total_amount,
      'billboards_count' => $this->contract->billboards->count(),
    ];
  }
}
