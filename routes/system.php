<?php

use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\TenantManagementController;
use App\Http\Controllers\System\UserManagementController;
use App\Http\Controllers\System\AnalyticsController;
use App\Http\Controllers\System\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Admin Routes
|--------------------------------------------------------------------------
|
| Routes for system administrators (super admins).
| These users have tenant_id === null and manage the entire system.
|
*/

Route::prefix('system')
    ->name('system.')
    ->middleware(['web', 'auth', 'super.admin'])
    ->group(function () {

        // System Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Tenant Management
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/', [TenantManagementController::class, 'index'])->name('index');
            Route::get('/create', [TenantManagementController::class, 'create'])->name('create');
            Route::post('/', [TenantManagementController::class, 'store'])->name('store');
            Route::get('/{tenant}', [TenantManagementController::class, 'show'])->name('show');
            Route::get('/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('edit');
            Route::put('/{tenant}', [TenantManagementController::class, 'update'])->name('update');
            Route::delete('/{tenant}', [TenantManagementController::class, 'destroy'])->name('destroy');

            // Tenant Actions
            Route::post('/{tenant}/activate', [TenantManagementController::class, 'activate'])->name('activate');
            Route::post('/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{tenant}/reset', [TenantManagementController::class, 'reset'])->name('reset');
        });

        // System User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');

            // User Actions
            Route::post('/{user}/impersonate', [UserManagementController::class, 'impersonate'])->name('impersonate');
            Route::post('/{user}/promote-to-super-admin', [UserManagementController::class, 'promoteToSuperAdmin'])->name('promote-super-admin');
            Route::post('/{user}/remove-from-tenant/{tenant}', [UserManagementController::class, 'removeFromTenant'])->name('remove-from-tenant');
        });

        // System Analytics & Reports
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/tenants', [AnalyticsController::class, 'tenants'])->name('tenants');
            Route::get('/users', [AnalyticsController::class, 'users'])->name('users');
            Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/usage', [AnalyticsController::class, 'usage'])->name('usage');
            Route::post('/export', [AnalyticsController::class, 'export'])->name('export');
        });

        // System Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
            Route::get('/maintenance', [SettingsController::class, 'maintenance'])->name('maintenance');
            Route::post('/maintenance/enable', [SettingsController::class, 'enableMaintenance'])->name('maintenance.enable');
            Route::post('/maintenance/disable', [SettingsController::class, 'disableMaintenance'])->name('maintenance.disable');
            Route::get('/backups', [SettingsController::class, 'backups'])->name('backups');
            Route::post('/backups/create', [SettingsController::class, 'createBackup'])->name('backups.create');
        });

        // System Health & Monitoring
        Route::prefix('health')->name('health.')->group(function () {
            Route::get('/', [SettingsController::class, 'health'])->name('index');
            Route::get('/database', [SettingsController::class, 'databaseHealth'])->name('database');
            Route::get('/cache', [SettingsController::class, 'cacheHealth'])->name('cache');
            Route::get('/storage', [SettingsController::class, 'storageHealth'])->name('storage');
        });
    });