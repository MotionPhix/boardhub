<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Settings extends Model implements HasMedia
{
  use InteractsWithMedia;

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

  public function registerMediaCollections(): void
  {
    $this
      ->addMediaCollection('logo')
      ->singleFile()
      ->useDisk('media')
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'])
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('thumbnail')
          ->width(200)
          ->height(200)
          ->performOnCollections('logo');
      });

    $this
      ->addMediaCollection('favicon')
      ->singleFile()
      ->useDisk('media')
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/x-icon'])
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('icon')
          ->width(32)
          ->height(32)
          ->performOnCollections('favicon');
      });
  }
}
