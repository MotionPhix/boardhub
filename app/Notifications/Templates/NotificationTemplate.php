<?php

namespace App\Notifications\Templates;

class NotificationTemplate
{
  protected static array $templates = [
    'contract.expiring' => [
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
    'contract.expired' => [
      'subject' => 'Contract Has Expired - {contract_number}',
      'greeting' => 'Contract Expiration Notice',
      'message' => 'The contract {contract_number} for {client_name} has expired. Please update the contract status accordingly.',
      'color' => 'danger',
      'icon' => 'heroicon-o-x-circle',
    ],
    'contract.renewal' => [
      'subject' => 'Contract Renewal Opportunity - {contract_number}',
      'greeting' => 'Contract Renewal',
      'message' => 'Contract {contract_number} for {client_name} is approaching renewal. Total contract value: ${total_amount}.',
      'color' => 'success',
      'icon' => 'heroicon-o-arrow-path',
    ],
  ];

  public static function get(string $template, string $urgency = null, array $data = []): array
  {
    $templateData = $urgency
      ? static::$templates[$template][$urgency]
      : static::$templates[$template];

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
}
