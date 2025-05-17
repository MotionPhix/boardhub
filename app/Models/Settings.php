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
    return [
      'timezone' => self::getAttribute('timezone') ?? config('app.timezone', 'UTC'),
      'locale' => self::getAttribute('locale') ?? config('app.locale', 'en'),
      'date_format' => self::getAttribute('date_format') ?? 'd M, Y',
      'time_format' => self::getAttribute('time_format') ?? 'H:i:s'
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
