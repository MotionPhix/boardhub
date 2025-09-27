<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin Gate - can do anything
        Gate::define('super-admin', function ($user) {
            return $user->hasRole('super-admin');
        });

        // Tenant Admin Gate - can manage their tenant
        Gate::define('tenant-admin', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('owner');
        });

        // Manager Gate - can manage resources within tenant
        Gate::define('manager', function ($user) {
            return $user->hasAnyRole(['owner', 'admin', 'manager']);
        });

        // Member Gate - basic access within tenant
        Gate::define('member', function ($user) {
            return $user->hasAnyRole(['owner', 'admin', 'manager', 'member']);
        });
    }
}