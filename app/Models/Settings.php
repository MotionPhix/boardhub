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
    'value' => 'json',
  ];

  public static function get(string $key, $default = null)
  {
    $setting = static::where('key', $key)->first();
    return $setting ? $setting->value : $default;
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

  public static function getDefaultCurrency(): array
  {
    return static::get('default_currency', [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
    ]);
  }

  public static function getLocalization(): array
  {
    return static::get('localization', [
      'timezone' => 'Africa/Blantyre',
      'locale' => 'en',
      'date_format' => 'Y-m-d',
      'time_format' => 'H:i',
    ]);
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

  public static function getAvailableCurrencies(): array
  {
    return static::get('currency_settings', [
      'MWK' => [
        'code' => 'MWK',
        'symbol' => 'MK',
        'name' => 'Malawian Kwacha',
      ],
      'USD' => [
        'code' => 'USD',
        'symbol' => '$',
        'name' => 'US Dollar',
      ],
      'ZMW' => [
        'code' => 'ZMW',
        'symbol' => 'ZK',
        'name' => 'Zambian Kwacha',
      ],
    ]);
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
