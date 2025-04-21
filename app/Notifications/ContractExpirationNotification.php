<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ContractExpirationNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected Contract $contract,
    protected int $daysUntilExpiration
  ) {}

  public function via($notifiable): array
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable): MailMessage
  {
    $urgencyLevel = $this->getUrgencyLevel();

    return (new MailMessage)
      ->subject("Contract Expiration Alert - {$this->contract->contract_number}")
      ->greeting("Hello {$notifiable->name},")
      ->line("This is a {$urgencyLevel} notice regarding contract: {$this->contract->contract_number}")
      ->line("Contract for client {$this->contract->client->name} will expire in {$this->daysUntilExpiration} days.")
      ->line("Contract Details:")
      ->line("- End Date: " . $this->contract->end_date->format('Y-m-d'))
      ->line("- Total Amount: $" . number_format($this->contract->total_amount, 2))
      ->line("- Number of Billboards: " . $this->contract->billboards()->count())
      ->action('View Contract', url("/admin/contracts/{$this->contract->id}"))
      ->line('Please take necessary action to either renew or close this contract.');
  }

  public function toDatabase($notifiable): array
  {
    return [
      'contract_id' => $this->contract->id,
      'message' => "Contract {$this->contract->contract_number} expires in {$this->daysUntilExpiration} days",
      'days_until_expiration' => $this->daysUntilExpiration,
      'client_name' => $this->contract->client->name,
      'end_date' => $this->contract->end_date->format('Y-m-d'),
    ];
  }

  public function toFilament($notifiable): FilamentNotification
  {
    $urgencyLevel = $this->getUrgencyLevel();

    return FilamentNotification::make()
      ->title("Contract Expiration - {$urgencyLevel}")
      ->icon('heroicon-o-clock')
      ->iconColor($this->getUrgencyColor())
      ->body("Contract {$this->contract->contract_number} for {$this->contract->client->name} expires in {$this->daysUntilExpiration} days")
      ->actions([
        \Filament\Notifications\Actions\Action::make('view')
          ->button()
          ->url(route('filament.admin.resources.contracts.edit', $this->contract))
          ->markAsRead(),
        \Filament\Notifications\Actions\Action::make('dismiss')
          ->close(),
      ])
      ->sendToDatabase();
  }

  protected function getUrgencyLevel(): string
  {
    return match(true) {
      $this->daysUntilExpiration <= 7 => 'URGENT',
      $this->daysUntilExpiration <= 14 => 'Important',
      default => 'Notice',
    };
  }

  protected function getUrgencyColor(): string
  {
    return match(true) {
      $this->daysUntilExpiration <= 7 => 'danger',
      $this->daysUntilExpiration <= 14 => 'warning',
      default => 'info',
    };
  }
}
