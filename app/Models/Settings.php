<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{

  protected $guarded = [];

  public static function instance(): self
  {
    return static::firstOrCreate();
  }

  public static function getLocalization(): array
  {
    $settings = self::instance();

    return [
      'timezone' => $settings->timezone ?? config('app.timezone', 'UTC'),
      'locale' => $settings->locale ?? config('app.locale', 'en'),
      'date_format' => $settings->date_format ?? 'd M, Y',
      'time_format' => $settings->time_format ?? 'H:i:s'
    ];
  }

  public static function getCompanyProfile(): array
  {
    $settings = self::instance();

    return [
      'name' => $settings->company_name ?? '',
      'email' => $settings->company_email ?? '',
      'phone' => $settings->company_phone ?? '',
      'address' => [
        'street' => $settings->company_address_street ?? '',
        'city' => $settings->company_address_city ?? '',
        'state' => $settings->company_address_state ?? '',
        'country' => $settings->company_address_country ?? '',
      ],
      'registration_number' => $settings->company_registration_number ?? '',
      'tax_number' => $settings->company_tax_number ?? '',
    ];
  }

  public static function getDocumentSettings(): array
  {
    $settings = self::instance();

    return [
      'contract_terms' => $settings->default_contract_terms ?? '',
      'contract_footer' => $settings->contract_footer_text ?? '',
    ];
  }

  public static function getBankingDetails(): array
  {
    $settings = self::instance();

    return [
      'bank_name' => $settings->bank_name ?? '',
      'account_name' => $settings->bank_account ?? '',
      'account_number' => $settings->account_number ?? '',
      'branch_code' => $settings->branch_code ?? '',
      'swift_code' => $settings->swift_code ?? '',
    ];
  }

}
