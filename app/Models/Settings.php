<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

  protected function value(): Attribute
  {
    return Attribute::make(
      get: fn ($value) => json_decode($value, true),
      set: function ($value) {
        // Handle special cases for different setting types
        if ($this->key === 'company_profile') {
          // Ensure the value has all required fields
          $defaultStructure = [
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
          ];

          // Merge with defaults to ensure structure
          $value = array_merge($defaultStructure, is_array($value) ? $value : []);
        }

        return json_encode($value);
      }
    );
  }

  /*public static function get(string $key, $default = null)
  {
    $setting = static::where('key', $key)->first();
    return $setting ? $setting->value : $default;
  }*/

  /**
   * Get a setting value by key with optional default value
   *
   * @param string $key
   * @param mixed|null $default
   * @return mixed
   */
  public static function get(string $key, $default = null)
  {
    $setting = static::where('key', strstr($key, '.', true) ?: $key)->first();

    if (!$setting) {
      return $default;
    }

    if (str_contains($key, '.')) {
      $keys = explode('.', $key);
      array_shift($keys); // Remove the first key as it's already used for the query
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
    static::updateOrCreate(
      ['key' => $key],
      [
        'value' => $value,
        'group' => $group,
      ]
    );
  }

  public static function getCompanyProfile(): array
  {
    return static::get('company_profile', [
      'name' => 'Your Company Name',
      'email' => 'info@company.com',
      'phone' => null,
      'address' => null,
      'registration_number' => null,
      'tax_number' => null,
    ]);
  }

  /**
   * Get the default currency settings
   */
  /*public static function getDefaultCurrency(): ?array
  {
    $settings = self::where('key', 'currency_settings')->first();

    if (!$settings) {
      return [
        'code' => 'MWK',
        'symbol' => 'MK',
        'name' => 'Malawian Kwacha',
        'is_default' => true,
      ];
    }

    // Find the default currency from the settings
    foreach ($settings->value as $currency) {
      if ($currency['is_default'] ?? false) {
        return $currency;
      }
    }

    // If no default is set, return the first currency or fallback
    return array_values($settings->value)[0] ?? [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
      'is_default' => true,
    ];
  }*/

  public static function getDefaultCurrency(): ?array
  {
    $settings = self::where('key', 'currency_settings')->first();

    if (!$settings || empty($settings->value)) {
      return [
        'code' => 'MWK',
        'symbol' => 'MK',
        'name' => 'Malawian Kwacha',
        'is_default' => true,
      ];
    }

    // Handle both array formats (indexed and associative)
    $currencies = $settings->value;

    // If indexed array
    if (isset($currencies[0])) {
      foreach ($currencies as $currency) {
        if ($currency['is_default'] ?? false) {
          return $currency;
        }
      }
      return $currencies[0];
    }

    // If associative array
    foreach ($currencies as $code => $currency) {
      if ($currency['is_default'] ?? false) {
        return $currency;
      }
    }

    // Return first currency if no default is set
    return reset($currencies);
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

  /**
   * Get all available currencies
   */
  /**
   * Get all available currencies
   */
  public static function getAvailableCurrencies(): array
  {
    $settings = self::where('key', 'currency_settings')->first();

    if (!$settings || empty($settings->value)) {
      // Return default currency if no settings exist
      $defaultCurrency = self::getDefaultCurrency();
      return [
        $defaultCurrency['code'] => $defaultCurrency
      ];
    }

    $currencies = $settings->value;

    // If the currencies are in indexed array format, convert to associative
    if (isset($currencies[0])) {
      $formattedCurrencies = [];
      foreach ($currencies as $currency) {
        if (!isset($currency['code'])) continue;
        $formattedCurrencies[$currency['code']] = [
          'code' => $currency['code'],
          'symbol' => $currency['symbol'] ?? '',
          'name' => $currency['name'] ?? $currency['code'],
          'is_default' => $currency['is_default'] ?? false,
        ];
      }
      return $formattedCurrencies;
    }

    // If already in associative format, ensure all required keys exist
    return collect($currencies)->map(function ($currency, $code) {
      return [
        'code' => $code,
        'symbol' => $currency['symbol'] ?? '',
        'name' => $currency['name'] ?? $code,
        'is_default' => $currency['is_default'] ?? false,
      ];
    })->toArray();
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

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('logo')
      ->singleFile()
      ->useDisk('public');

    $this->addMediaCollection('favicon')
      ->singleFile()
      ->useDisk('public');

    $this->addMediaCollection('templates')
      ->useDisk('public');
  }
}
