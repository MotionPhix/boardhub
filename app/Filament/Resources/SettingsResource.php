<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Settings;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class SettingsResource extends Resource
{
  protected static ?string $model = Settings::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
  protected static ?string $navigationGroup = 'System';
  protected static ?string $navigationLabel = 'Settings';
  protected static ?int $navigationSort = 100;

  public static function getPages(): array
  {
    return [
      'index' => Pages\EditSettings::route('/'),
    ];
  }

  public static function canCreate(): bool
  {
    return false;
  }

  public static function canDelete(Model $record): bool
  {
    return false;
  }

  public static function canDeleteAny(): bool
  {
    return false;
  }
}
