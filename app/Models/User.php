<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, HasPermissions;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'avatar',
    'phone',
    'bio',
    'is_admin',
    'is_active',
    'email_verified_at',
    'notification_preferences',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'notification_preferences' => 'array',
      'password' => 'hashed',
      'is_admin' => 'boolean',
      'is_active' => 'boolean',
    ];
  }

  public function loginActivities()
  {
    return $this->hasMany(UserLoginActivity::class);
  }

  public function canAccessPanel(Panel $panel): bool
  {
    return $this->is_admin || $this->hasRole('admin');
  }

  public function canImpersonate(): bool
  {
    return $this->is_admin;
  }

  public function notificationSettings()
  {
    return $this->hasMany(NotificationSettings::class);
  }

  public function routeNotificationForMail()
  {
    return $this->email;
  }

  public function shouldReceiveNotification(string $type, string $channel): bool
  {
    return $this->notificationSettings()
      ->where('type', $type)
      ->where('channel', $channel)
      ->where('is_enabled', true)
      ->exists();
  }
}
