<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ContractExpiryNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract,
    protected int $daysUntilExpiry
  ) {}

  public function via($notifiable): array
  {
    return $this->getEnabledChannels($notifiable, 'contract_expiry');
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject("Contract Expiring in {$this->daysUntilExpiry} days")
      ->line("Contract #{$this->contract->id} is expiring in {$this->daysUntilExpiry} days.")
      ->line("Billboard: {$this->contract->billboards->first()->name}")
      ->line("Client: {$this->contract->client->name}")
      ->action('View Contract', url("/admin/contracts/{$this->contract->id}"))
      ->line('Please take necessary action.');
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'days_until_expiry' => $this->daysUntilExpiry,
      'billboard_name' => $this->contract->billboards->first()->name,
      'client_name' => $this->contract->client->name,
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'title' => "Contract Expiring Soon",
      'body' => "Contract #{$this->contract->id} is expiring in {$this->daysUntilExpiry} days",
      'data' => $this->toArray($notifiable),
    ]);
  }

  protected function getEnabledChannels($notifiable, string $type): array
  {
    return $notifiable->notificationSettings()
      ->where('type', $type)
      ->where('is_enabled', true)
      ->pluck('channel')
      ->toArray();
  }
}
