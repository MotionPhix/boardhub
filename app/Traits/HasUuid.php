<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
  public static function bootHasUuid()
  {
    static::creating(function ($model) {
      $model->uuid = Str::orderedUuid();
    });

    static::updating(function ($model) {
      if (! $model->uuid) $model->uuid = Str::orderedUuid();
    });
  }
}
