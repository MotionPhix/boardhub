<?php

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\BillboardController;
use App\Http\Controllers\Tenant\BookingController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes
|--------------------------------------------------------------------------
|
| Routes for tenant owners, admins, and managers.
| These users manage billboards, bookings, and team within their tenant.
|
*/

Route::prefix('tenant/{tenant}/admin')
    ->name('tenant.')
    ->middleware(['web', 'auth', 'resolve.tenant', 'tenant.admin'])
    ->group(function () {

        // Tenant Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Billboard Management
        Route::prefix('billboards')->name('billboards.')->group(function () {
            Route::get('/', [BillboardController::class, 'index'])->name('index');
            Route::get('/create', [BillboardController::class, 'create'])->name('create');
            Route::post('/', [BillboardController::class, 'store'])->name('store');
            Route::get('/{billboard}', [BillboardController::class, 'show'])->name('show');
            Route::get('/{billboard}/edit', [BillboardController::class, 'edit'])->name('edit');
            Route::put('/{billboard}', [BillboardController::class, 'update'])->name('update');
            Route::delete('/{billboard}', [BillboardController::class, 'destroy'])->name('destroy');

            // Billboard Actions
            Route::post('/{billboard}/toggle-availability', [BillboardController::class, 'toggleAvailability'])->name('toggle-availability');
            Route::post('/{billboard}/bulk-update', [BillboardController::class, 'bulkUpdate'])->name('bulk-update');
            Route::get('/{billboard}/bookings', [BillboardController::class, 'bookings'])->name('bookings');
        });

        // Booking Management
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('index');
            Route::get('/create', [BookingController::class, 'create'])->name('create');
            Route::post('/', [BookingController::class, 'store'])->name('store');
            Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
            Route::get('/{booking}/edit', [BookingController::class, 'edit'])->name('edit');
            Route::put('/{booking}', [BookingController::class, 'update'])->name('update');
            Route::delete('/{booking}', [BookingController::class, 'destroy'])->name('destroy');

            // Booking Actions
            Route::post('/{booking}/approve', [BookingController::class, 'approve'])->name('approve');
            Route::post('/{booking}/reject', [BookingController::class, 'reject'])->name('reject');
            Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
            Route::post('/{booking}/complete', [BookingController::class, 'complete'])->name('complete');
            Route::get('/{booking}/contract', [BookingController::class, 'generateContract'])->name('contract');
        });

        // Team Management
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::get('/create', [TeamController::class, 'create'])->name('create');
            Route::post('/', [TeamController::class, 'store'])->name('store');
            Route::get('/{user}', [TeamController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [TeamController::class, 'edit'])->name('edit');
            Route::put('/{user}', [TeamController::class, 'update'])->name('update');
            Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');

            // Team Actions
            Route::post('/invite', [TeamController::class, 'invite'])->name('invite');
            Route::post('/{user}/change-role', [TeamController::class, 'changeRole'])->name('change-role');
            Route::post('/{user}/suspend', [TeamController::class, 'suspend'])->name('suspend');
            Route::post('/{user}/reactivate', [TeamController::class, 'reactivate'])->name('reactivate');
            Route::get('/invitations/pending', [TeamController::class, 'pendingInvitations'])->name('invitations.pending');
        });

        // Analytics & Reports
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/bookings', [AnalyticsController::class, 'bookings'])->name('bookings');
            Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/billboards', [AnalyticsController::class, 'billboards'])->name('billboards');
            Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
            Route::post('/export', [AnalyticsController::class, 'export'])->name('export');
        });

        // Tenant Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
            Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
            Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
            Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::get('/api-keys', [SettingsController::class, 'apiKeys'])->name('api-keys');
            Route::post('/api-keys', [SettingsController::class, 'generateApiKey'])->name('api-keys.generate');
            Route::delete('/api-keys/{key}', [SettingsController::class, 'revokeApiKey'])->name('api-keys.revoke');
        });

        // Tenant Data Export/Import
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/export', [SettingsController::class, 'exportData'])->name('export');
            Route::post('/import', [SettingsController::class, 'importData'])->name('import');
            Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        });
    });