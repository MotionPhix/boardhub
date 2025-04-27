<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ContractRenewalNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract
  ) {}

  public function via($notifiable): array
  {
    return $this->getEnabledChannels($notifiable, 'contract_renewal');
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject('Contract Ready for Renewal')
      ->line("Contract #{$this->contract->id} is ready for renewal.")
      ->line("Billboard: {$this->contract->billboards->first()->name}")
      ->line("Client: {$this->contract->client->name}")
      ->action('View Contract', url("/admin/contracts/{$this->contract->id}"))
      ->line('Please review and process the renewal.');
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'billboard_name' => $this->contract->billboards->first()->name,
      'client_name' => $this->contract->client->name,
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'title' => 'Contract Renewal',
      'body' => "Contract #{$this->contract->id} is ready for renewal",
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
