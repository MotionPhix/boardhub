<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\HtmlString;

class ContractNotification extends BaseNotification
{
  public const TYPE_EXPIRY = 'expiry';
  public const TYPE_STATUS_CHANGE = 'status_change';
  public const TYPE_ACTIVATION = 'activation';

  public function __construct(
    protected Contract $contract,
    protected string $type,
    protected array $additionalData = []
  ) {}

  public function via($notifiable): array
  {
    $channels = $this->getEnabledChannels($notifiable, "contract_{$this->type}");

    // Always include database channel for in-app notifications
    if (!in_array('database', $channels)) {
      $channels[] = 'database';
    }

    return $channels;
  }

  public function toMail($notifiable): MailMessage
  {
    $template = $this->getTemplateData($notifiable);

    $mail = (new MailMessage)
      ->subject($template['subject'])
      ->greeting($template['greeting']);

    // Add contract details
    $mail->line(new HtmlString("<strong>Contract Details:</strong>"))
      ->line("Contract Number: {$this->contract->contract_number}")
      ->line("Client: {$this->contract->client->name}");

    // Add type-specific content
    match ($this->type) {
      self::TYPE_EXPIRY => $this->addExpiryContent($mail),
      self::TYPE_STATUS_CHANGE => $this->addStatusChangeContent($mail),
      self::TYPE_ACTIVATION => $this->addActivationContent($mail),
    };

    return $mail->action(
      'View Contract',
      route('filament.admin.resources.contracts.view', $this->contract)
    );
  }

  public function toArray($notifiable): array
  {
    $template = $this->getTemplateData($notifiable);

    return [
      'contract_id' => $this->contract->id,
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'type' => $this->type,
      'message' => $template['message'],
      'color' => $template['color'] ?? 'primary',
      'icon' => $template['icon'] ?? 'heroicon-o-document-text',
      'additional_data' => $this->additionalData,
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage($this->toArray($notifiable));
  }

  protected function getTemplateData($notifiable): array
  {
    $urgency = $this->determineUrgency();

    return $this->getTemplate("contract.{$this->type}", $urgency, [
      'contract_number' => $this->contract->contract_number,
      'client_name' => $this->contract->client->name,
      'days' => $this->additionalData['days'] ?? null,
      'total_amount' => number_format($this->contract->contract_total, 2),
      'old_status' => $this->additionalData['old_status'] ?? null,
      'new_status' => $this->additionalData['new_status'] ?? null,
    ]);
  }

  protected function determineUrgency(): ?string
  {
    if ($this->type !== self::TYPE_EXPIRY) {
      return null;
    }

    $days = $this->additionalData['days'] ?? 0;
    return match (true) {
      $days <= 3 => 'urgent',
      $days <= 7 => 'warning',
      default => 'notice'
    };
  }

  private function addExpiryContent(MailMessage $mail): void
  {
    $days = $this->additionalData['days'] ?? 0;
    $mail->line("Days until expiry: {$days}")
      ->line("End Date: {$this->contract->end_date->format('Y-m-d')}");
  }

  private function addStatusChangeContent(MailMessage $mail): void
  {
    $oldStatus = $this->additionalData['old_status'] ?? '';
    $newStatus = $this->additionalData['new_status'] ?? '';

    $mail->line("Previous Status: " . ucfirst($oldStatus))
      ->line("New Status: " . ucfirst($newStatus));
  }

  private function addActivationContent(MailMessage $mail): void
  {
    $billboardsList = $this->contract->billboards
      ->map(fn($billboard) => "- {$billboard->name}")
      ->join("\n");

    $mail->line("Total Amount: MK " . number_format($this->contract->contract_total, 2))
      ->line(new HtmlString("<strong>Billboards:</strong>"))
      ->line(new HtmlString("<pre>{$billboardsList}</pre>"));
  }
}
