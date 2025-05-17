<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Settings extends Model implements HasMedia
{
  use InteractsWithMedia;

  protected $fillable = [
    'key',
    'value',
    'group',
  ];

  protected $casts = [
    'value' => 'array',
  ];

  // Define setting keys as constants for consistency
  public const KEY_COMPANY_PROFILE = 'company_profile';
  public const KEY_CURRENCY_SETTINGS = 'currency_settings';
  public const KEY_LOCALIZATION = 'localization';
  public const KEY_DOCUMENT_SETTINGS = 'document_settings';
  public const KEY_BILLBOARD_CODE_FORMAT = 'billboard_code_format';

  // Define setting groups as constants
  public const GROUP_COMPANY = 'company';
  public const GROUP_SYSTEM = 'system';
  public const GROUP_DOCUMENTS = 'documents';
  public const GROUP_BILLBOARDS = 'billboards';

  public static function get(string $key, $default = null)
  {
    $setting = static::where('key', strstr($key, '.', true) ?: $key)->first();

    if (!$setting) {
      return $default;
    }

    if (str_contains($key, '.')) {
      $keys = explode('.', $key);
      array_shift($keys);
      $value = $setting->value;

      foreach ($keys as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
          return $default;
        }
        $value = $value[$k];
      }

      return $value;
    }

    return $setting->value ?? $default;
  }

  public static function set(string $key, $value, string $group = 'general'): void
  {
    // Ensure proper structure based on key
    $value = self::getStructuredValue($key, $value);

    static::updateOrCreate(
      ['key' => $key],
      [
        'value' => $value,
        'group' => $group,
      ]
    );
  }

  protected static function getStructuredValue(string $key, $value): array
  {
    $value = is_array($value) ? $value : [];

    return match ($key) {
      self::KEY_COMPANY_PROFILE => array_merge([
        'name' => null,
        'email' => null,
        'phone' => null,
        'address' => [
          'street' => null,
          'city' => null,
          'state' => null,
          'country' => null,
        ],
        'registration_number' => null,
        'tax_number' => null,
      ], $value),

      self::KEY_CURRENCY_SETTINGS => collect($value)
        ->map(fn($currency) => array_merge([
          'code' => '',
          'symbol' => '',
          'name' => '',
          'is_default' => false,
        ], $currency))
        ->toArray(),

      self::KEY_LOCALIZATION => array_merge([
        'timezone' => config('app.timezone', 'UTC'),
        'locale' => config('app.locale', 'en'),
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
      ], $value),

      self::KEY_DOCUMENT_SETTINGS => array_merge([
        'default_contract_terms' => null,
        'contract_footer_text' => null,
      ], $value),

      self::KEY_BILLBOARD_CODE_FORMAT => array_merge([
        'prefix' => 'BH',
        'separator' => '-',
        'counter_length' => 5,
      ], $value),

      default => $value,
    };
  }

  public static function getCompanyProfile(): array
  {
    return self::get(self::KEY_COMPANY_PROFILE, [
      'name' => 'Your Company Name',
      'email' => 'info@company.com',
      'phone' => null,
      'address' => [
        'street' => null,
        'city' => null,
        'state' => null,
        'country' => null,
      ],
      'registration_number' => null,
      'tax_number' => null,
    ]);
  }

  public static function getDefaultCurrency(): array
  {
    $currencies = self::get(self::KEY_CURRENCY_SETTINGS, []);

    // Handle both indexed and associative arrays
    $currencies = array_values($currencies);

    // Find default currency
    $defaultCurrency = collect($currencies)->firstWhere('is_default', true);

    if (!$defaultCurrency && !empty($currencies)) {
      $defaultCurrency = $currencies[0];
    }

    return $defaultCurrency ?? [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
      'is_default' => true,
    ];
  }

  public static function getAvailableCurrencies(): array
  {
    $currencies = self::get(self::KEY_CURRENCY_SETTINGS, []);

    if (empty($currencies)) {
      return [self::getDefaultCurrency()];
    }

    return collect($currencies)
      ->map(fn($currency) => array_merge([
        'code' => '',
        'symbol' => '',
        'name' => '',
        'is_default' => false,
      ], $currency))
      ->toArray();
  }

  public static function getAvailableCountries(): array
  {
    return Country::query()
      ->where('is_active', true)
      ->get()
      ->mapWithKeys(fn (Country $country) => [
        $country->code => [
          'code' => $country->code,
          'name' => $country->name,
          'is_default' => $country->is_default,
        ]
      ])
      ->toArray();
  }

  public static function getDefaultCountry(): array
  {
    $defaultCountry = Country::query()
      ->where('is_active', true)
      ->where('is_default', true)
      ->first();

    if (!$defaultCountry) {
      $defaultCountry = Country::query()
        ->where('is_active', true)
        ->first();
    }

    return $defaultCountry ? [
      'code' => $defaultCountry->code,
      'name' => $defaultCountry->name,
    ] : [
      'code' => 'MW',
      'name' => 'Malawi',
    ];
  }

  public static function getLocalization(): array
  {
    $settings = self::where('key', 'localization')->first();

    return [
      'timezone' => $settings?->value['timezone'] ?? config('app.timezone', 'UTC'),
      'locale' => $settings?->value['locale'] ?? config('app.locale', 'en'),
      'date_format' => $settings?->value['date_format'] ?? 'Y-m-d',
      'time_format' => $settings?->value['time_format'] ?? 'H:i:s'
    ];
  }

  public static function getDocumentSettings(): array
  {
    return static::get('document_settings', [
      'contract_footer_text' => null,
      'default_payment_terms' => [
        ['days' => 30, 'description' => 'Net 30'],
        ['days' => 15, 'description' => 'Net 15'],
      ],
      'default_contract_terms' => null,
    ]);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('logo')
      ->singleFile()
      ->useDisk('public');

    $this->addMediaCollection('favicon')
      ->singleFile()
      ->useDisk('public');
  }
}
