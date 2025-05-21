<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractTemplate extends Model
{
  use SoftDeletes, HasUuid;

  protected $fillable = [
    'name',
    'description',
    'content',
    'is_default',
    'variables',
    'preview_image',
    'template_type',
  ];

  protected $casts = [
    'is_default' => 'boolean',
    'variables' => 'array',
    'settings' => 'array',
  ];

  public function contracts(): HasMany
  {
    return $this->hasMany(Contract::class, 'template_id');
  }

  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  public function getPreviewImageUrlAttribute()
  {
    return $this->preview_image ? asset('storage/' . $this->preview_image) : null;
  }

  public static function getDefaultTemplate()
  {
    return static::where('is_default', true)->first() ?? static::first();
  }

  public function replaceVariables(array $variables): string
  {
    $content = $this->content;

    foreach ($variables as $key => $value) {
      $content = str_replace('{{'.$key.'}}', $value, $content);
    }

    return $content;
  }
}
