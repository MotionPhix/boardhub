<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
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

  public static function getDefaultCurrency(): array
  {
    return static::get('default_currency', [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
    ]);
  }

  public static function getAvailableCurrencies(): array
  {
    return [
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
      // Add more currencies as needed
    ];
  }
}
