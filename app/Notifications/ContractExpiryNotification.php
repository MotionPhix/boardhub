<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Notifications\Templates\NotificationTemplate;

class ContractExpiryNotification extends BaseNotification
{
  protected array $templateData;
  protected array $template;

  public function __construct(
    protected Contract $contract,
    protected int $daysUntilExpiry
  ) {
    $this->templateData = [
      'days' => $this->daysUntilExpiry,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'total_amount' => number_format($this->contract->total_amount, 2),
      'end_date' => $this->contract->end_date->format('Y-m-d'),
    ];

    $this->template = NotificationTemplate::get(
      'contract.expiring',
      $this->getUrgencyLevel(),
      $this->templateData
    );
  }

  public function via($notifiable): array
  {
    if (!$this->shouldSendNotification($notifiable, 'contract_expiry')) {
      return [];
    }

    return $this->getEnabledChannels($notifiable, 'contract_expiry');
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject($this->template['subject'])
      ->markdown('emails.contract-notification', [
        'greeting' => $this->template['greeting'],
        'message' => $this->template['message'],
        'details' => [
          'End Date' => $this->contract->end_date->format('Y-m-d'),
          'Days Until Expiry' => $this->daysUntilExpiry,
          'Total Amount' => 'MK ' . number_format($this->contract->total_amount, 2),
          'Client' => $this->contract->client->name,
        ],
        'actionUrl' => route('filament.admin.resources.contracts.edit', $this->contract),
        'actionText' => 'View Contract',
        'color' => $this->template['color'],
      ]);
  }

  public function toArray($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'days_until_expiry' => $this->daysUntilExpiry,
      'message' => $this->template['message'],
      'urgency' => $this->getUrgencyLevel(),
      'icon' => $this->template['icon'],
      'color' => $this->template['color'],
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'title' => $this->template['subject'],
      'body' => $this->template['message'],
      'data' => $this->toArray($notifiable),
    ]);
  }

  protected function getUrgencyLevel(): string
  {
    return match(true) {
      $this->daysUntilExpiry <= 3 => 'urgent',
      $this->daysUntilExpiry <= 7 => 'warning',
      default => 'notice',
    };
  }
}
