<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
  protected $fillable = [
    'code',
    'symbol',
    'name',
    'is_default',
  ];

  protected $casts = [
    'is_default' => 'boolean',
  ];

  public static function getDefault(): ?self
  {
    return static::where('is_default', true)->first() ?? static::first();
  }

  protected static function booted()
  {
    static::saving(function (Currency $currency) {
      if ($currency->is_default) {
        static::where('id', '!=', $currency->id)->update(['is_default' => false]);
      }
    });
  }
}
