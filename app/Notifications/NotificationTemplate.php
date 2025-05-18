<?php

namespace App\Notifications;

class NotificationTemplate
{
  protected static array $templates = [
    'contract.expiry' => [
      'urgent' => [
        'subject' => 'URGENT: Contract Expiring in {days} Days - {contract_number}',
        'greeting' => 'Immediate Action Required',
        'message' => 'The contract {contract_number} for {client_name} is expiring in {days} days. This requires your urgent attention.',
        'color' => 'danger',
        'icon' => 'heroicon-o-exclamation-triangle',
      ],
      'warning' => [
        'subject' => 'Important: Contract Expiring Soon - {contract_number}',
        'greeting' => 'Important Notice',
        'message' => 'Contract {contract_number} for {client_name} will expire in {days} days. Please review and take necessary action.',
        'color' => 'warning',
        'icon' => 'heroicon-o-clock',
      ],
      'notice' => [
        'subject' => 'Contract Expiration Notice - {contract_number}',
        'greeting' => 'Contract Update',
        'message' => 'This is a friendly reminder that contract {contract_number} for {client_name} will expire in {days} days.',
        'color' => 'info',
        'icon' => 'heroicon-o-information-circle',
      ],
    ],
    'contract.status_change' => [
      'subject' => 'Contract Status Updated - {contract_number}',
      'greeting' => 'Contract Status Change',
      'message' => 'Contract {contract_number} status has changed from {old_status} to {new_status}.',
      'color' => 'primary',
      'icon' => 'heroicon-o-arrow-path',
    ],
    'contract.activation' => [
      'subject' => 'Contract Activated - {contract_number}',
      'greeting' => 'Contract Activation Notice',
      'message' => 'Contract {contract_number} for {client_name} has been activated. Total value: MK {total_amount}',
      'color' => 'success',
      'icon' => 'heroicon-o-check-circle',
    ],
    'contract.completion' => [
      'subject' => 'Contract Completed - {contract_number}',
      'greeting' => 'Contract Completion Notice',
      'message' => 'Contract {contract_number} for {client_name} has been marked as completed.',
      'color' => 'success',
      'icon' => 'heroicon-o-check-badge',
    ],
    'contract.cancellation' => [
      'subject' => 'Contract Cancelled - {contract_number}',
      'greeting' => 'Contract Cancellation Notice',
      'message' => 'Contract {contract_number} for {client_name} has been cancelled.',
      'color' => 'danger',
      'icon' => 'heroicon-o-x-circle',
    ]
  ];

  public static function get(string $template, ?string $urgency = null, array $data = []): array
  {
    $templateData = $urgency && isset(static::$templates[$template][$urgency])
      ? static::$templates[$template][$urgency]
      : static::$templates[$template] ?? [];

    if (empty($templateData)) {
      throw new \InvalidArgumentException("Template not found: {$template}" . ($urgency ? " with urgency: {$urgency}" : ''));
    }

    return array_map(function ($value) use ($data) {
      return static::replacePlaceholders($value, $data);
    }, $templateData);
  }

  protected static function replacePlaceholders(string $text, array $data): string
  {
    foreach ($data as $key => $value) {
      $text = str_replace("{{$key}}", $value, $text);
    }
    return $text;
  }

  public static function getAllTemplates(): array
  {
    return static::$templates;
  }
}
