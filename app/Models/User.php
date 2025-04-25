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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

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

// Add this relationship to your User model
  public function notificationSettings()
  {
    return $this->hasOne(NotificationSettings::class);
  }

// Add this to determine if user should receive specific notifications
  public function shouldNotifyForDays(int $days): bool
  {
    $settings = $this->notificationSettings;
    if (!$settings) return true;

    return in_array($days, $settings->notification_thresholds);
  }
}
