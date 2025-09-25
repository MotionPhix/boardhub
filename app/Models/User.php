<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, BelongsToTenant, HasRoles;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_active',
        'phone',
        'avatar',
        'position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Super admin panel access
        if ($panel->getId() === 'admin') {
            return $this->tenant_id === null; // Only super admins (no tenant_id)
        }

        // Tenant panel access
        if ($panel->getId() === 'tenant') {
            return $this->tenant_id !== null && app()->bound('tenant') &&
                   $this->tenant_id === app('tenant')->id;
        }

        return true;
    }
}
