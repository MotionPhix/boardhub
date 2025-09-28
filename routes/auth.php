<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorAuthController;
// use App\Http\Controllers\Auth\SocialAuthController; // Disabled - requires Laravel Socialite
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Military-grade authentication with 2FA and comprehensive security.
| Laravel Fortify handles most authentication, these are additional routes.
|
*/

// Enhanced Authentication Routes
Route::middleware('guest')->group(function () {

    // Social Authentication (disabled - requires Laravel Socialite package)
    // Route::prefix('auth')->name('auth.')->group(function () {
    //     Route::get('/{provider}', [SocialAuthController::class, 'redirect'])
    //         ->name('social.redirect')
    //         ->where('provider', 'google|github|linkedin');

    //     Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
    //         ->name('social.callback')
    //         ->where('provider', 'google|github|linkedin');
    // });

    // Custom login enhancements
    Route::post('/login/security-check', [AuthenticatedSessionController::class, 'securityCheck'])
        ->name('login.security-check');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Two-Factor Authentication Management (Custom)
    Route::prefix('two-factor')->name('two-factor.custom.')->group(function () {
        Route::get('/setup', [TwoFactorAuthController::class, 'setup'])->name('setup');
        Route::post('/enable', [TwoFactorAuthController::class, 'enable'])->name('enable');
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])->name('confirm');
        Route::post('/disable', [TwoFactorAuthController::class, 'disable'])->name('disable');
        Route::get('/recovery-codes', [TwoFactorAuthController::class, 'recoveryCodes'])->name('recovery-codes');
        Route::post('/regenerate-recovery-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])
            ->name('regenerate-recovery-codes');
    });

    // Enhanced Session Management
    Route::prefix('sessions')->name('sessions.')->group(function () {
        Route::get('/', [AuthenticatedSessionController::class, 'activeSessions'])->name('index');
        Route::post('/{session}/revoke', [AuthenticatedSessionController::class, 'revokeSession'])->name('revoke');
        Route::post('/revoke-all', [AuthenticatedSessionController::class, 'revokeAllSessions'])->name('revoke-all');
    });

    // Tenant Switching and Management
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [TenantSwitchController::class, 'index'])->name('select');
        Route::post('/switch', [TenantSwitchController::class, 'switch'])->name('switch');
        Route::post('/logout/{tenant_id}', [TenantSwitchController::class, 'logoutFromTenant'])->name('logout');
        Route::post('/invitations/accept', [TenantSwitchController::class, 'acceptInvitation'])->name('invitations.accept');
        Route::post('/refresh', [TenantSwitchController::class, 'refreshAccessibleTenants'])->name('refresh');

        // API endpoints for tenant switching
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/accessible', [TenantSwitchController::class, 'getAccessibleTenants'])->name('accessible');
            Route::get('/context', [TenantSwitchController::class, 'getTenantContext'])->name('context');
            Route::get('/breadcrumbs', [TenantSwitchController::class, 'getBreadcrumbs'])->name('breadcrumbs');
            Route::post('/validate-session', [TenantSwitchController::class, 'validateSession'])->name('validate-session');
        });
    });

    // Account Security Settings
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [AuthenticatedSessionController::class, 'securitySettings'])->name('index');
        Route::post('/change-password', [AuthenticatedSessionController::class, 'changePassword'])->name('change-password');
        Route::get('/login-history', [AuthenticatedSessionController::class, 'loginHistory'])->name('login-history');
        Route::post('/download-data', [AuthenticatedSessionController::class, 'downloadData'])->name('download-data');
        Route::post('/delete-account', [AuthenticatedSessionController::class, 'deleteAccount'])->name('delete-account');
    });

    // Security Notifications
    Route::prefix('security-notifications')->name('security-notifications.')->group(function () {
        Route::get('/', [AuthenticatedSessionController::class, 'securityNotifications'])->name('index');
        Route::post('/{notification}/acknowledge', [AuthenticatedSessionController::class, 'acknowledgeNotification'])
            ->name('acknowledge');
        Route::post('/mark-all-read', [AuthenticatedSessionController::class, 'markAllNotificationsRead'])
            ->name('mark-all-read');
    });
});

// Public Security Endpoints
Route::prefix('security')->name('security.')->group(function () {

    // Security incident reporting
    Route::post('/report-incident', [AuthenticatedSessionController::class, 'reportIncident'])
        ->name('report-incident');

    // Security status check (for monitoring)
    Route::get('/status', [AuthenticatedSessionController::class, 'securityStatus'])
        ->name('status');
});

// Note: Login and register routes are handled by Laravel Fortify
// Fortify automatically registers these routes:
// GET/POST /login, GET/POST /register, POST /logout, etc.

Route::get('/select-tenant', function() {
    return redirect()->route('tenants.select');
})->name('select-tenant');