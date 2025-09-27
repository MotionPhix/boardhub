<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Admin\BillboardManagementController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\TwoFactorEnabled;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the custom admin panel with military-grade security.
| All routes require authentication and appropriate permissions.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', AdminAuth::class, TwoFactorEnabled::class])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.main');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');

    // Super Admin Only Routes
    Route::middleware('can:super-admin')->group(function () {

        // System Settings
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
            Route::get('/maintenance', [SystemSettingsController::class, 'maintenance'])->name('maintenance');
            Route::post('/maintenance/toggle', [SystemSettingsController::class, 'toggleMaintenance'])->name('maintenance.toggle');
            Route::get('/cache', [SystemSettingsController::class, 'cache'])->name('cache');
            Route::post('/cache/clear', [SystemSettingsController::class, 'clearCache'])->name('cache.clear');
        });

        // Global Tenant Management
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/', [TenantManagementController::class, 'index'])->name('index');
            Route::get('/create', [TenantManagementController::class, 'create'])->name('create');
            Route::post('/', [TenantManagementController::class, 'store'])->name('store');
            Route::get('/{tenant}', [TenantManagementController::class, 'show'])->name('show');
            Route::get('/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('edit');
            Route::put('/{tenant}', [TenantManagementController::class, 'update'])->name('update');
            Route::delete('/{tenant}', [TenantManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{tenant}/activate', [TenantManagementController::class, 'activate'])->name('activate');
        });

        // Global User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
            Route::post('/stop-impersonation', [UserController::class, 'stopImpersonation'])->name('stop-impersonation');
            Route::post('/{user}/toggle-2fa', [UserController::class, 'toggleTwoFactor'])->name('toggle-2fa');
        });

        // Payment Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentManagementController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentManagementController::class, 'show'])->name('show');
            Route::post('/{payment}/refund', [PaymentManagementController::class, 'refund'])->name('refund');
            Route::get('/analytics/revenue', [PaymentManagementController::class, 'revenueAnalytics'])->name('analytics.revenue');
            Route::get('/analytics/trends', [PaymentManagementController::class, 'trends'])->name('analytics.trends');
        });

        // Security & Audit Logs
        Route::prefix('security')->name('security.')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
            Route::get('/failed-logins', [SecurityController::class, 'failedLogins'])->name('failed-logins');
            Route::get('/sessions', [SecurityController::class, 'activeSessions'])->name('sessions');
            Route::post('/sessions/{session}/revoke', [SecurityController::class, 'revokeSession'])->name('sessions.revoke');
            Route::get('/two-factor-stats', [SecurityController::class, 'twoFactorStats'])->name('two-factor-stats');
        });
    });

    // Tenant Admin Routes (for their own tenant)
    Route::middleware('can:tenant-admin')->group(function () {

        // Billboard Management (Tenant Scoped)
        Route::prefix('billboards')->name('billboards.')->group(function () {
            Route::get('/', [BillboardManagementController::class, 'index'])->name('index');
            Route::get('/create', [BillboardManagementController::class, 'create'])->name('create');
            Route::post('/', [BillboardManagementController::class, 'store'])->name('store');
            Route::get('/{billboard}', [BillboardManagementController::class, 'show'])->name('show');
            Route::get('/{billboard}/edit', [BillboardManagementController::class, 'edit'])->name('edit');
            Route::put('/{billboard}', [BillboardManagementController::class, 'update'])->name('update');
            Route::delete('/{billboard}', [BillboardManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{billboard}/activate', [BillboardManagementController::class, 'activate'])->name('activate');
            Route::post('/{billboard}/deactivate', [BillboardManagementController::class, 'deactivate'])->name('deactivate');
        });

        // Tenant User Management
        Route::prefix('tenant-users')->name('tenant-users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'tenantUsers'])->name('index');
            Route::post('/invite', [UserManagementController::class, 'inviteUser'])->name('invite');
            Route::put('/{user}/role', [UserManagementController::class, 'updateRole'])->name('update-role');
            Route::delete('/{user}/remove', [UserManagementController::class, 'removeFromTenant'])->name('remove');
        });

        // Tenant Settings
        Route::prefix('tenant-settings')->name('tenant-settings.')->group(function () {
            Route::get('/', [SystemSettingsController::class, 'tenantSettings'])->name('index');
            Route::put('/', [SystemSettingsController::class, 'updateTenantSettings'])->name('update');
            Route::get('/branding', [SystemSettingsController::class, 'branding'])->name('branding');
            Route::put('/branding', [SystemSettingsController::class, 'updateBranding'])->name('branding.update');
        });
    });

    // Manager Level Routes
    Route::middleware('can:manager')->group(function () {

        // Reports & Analytics
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [DashboardController::class, 'reports'])->name('index');
            Route::get('/bookings', [DashboardController::class, 'bookingReports'])->name('bookings');
            Route::get('/revenue', [DashboardController::class, 'revenueReports'])->name('revenue');
            Route::get('/performance', [DashboardController::class, 'performanceReports'])->name('performance');
            Route::post('/export', [DashboardController::class, 'exportReport'])->name('export');
        });
    });

    // Profile Management (All authenticated users)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserManagementController::class, 'profile'])->name('index');
        Route::put('/', [UserManagementController::class, 'updateProfile'])->name('update');
        Route::get('/security', [SecurityController::class, 'personalSecurity'])->name('security');
        Route::post('/enable-2fa', [SecurityController::class, 'enableTwoFactor'])->name('enable-2fa');
        Route::post('/disable-2fa', [SecurityController::class, 'disableTwoFactor'])->name('disable-2fa');
        Route::get('/sessions', [SecurityController::class, 'personalSessions'])->name('sessions');
        Route::post('/revoke-session/{session}', [SecurityController::class, 'revokePersonalSession'])->name('revoke-session');
    });
});