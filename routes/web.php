<?php

use App\Http\Controllers\BillboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TenantOnboardingController;
use Illuminate\Support\Facades\Route;

// Multi-tenant routes with UUID parameter for security
Route::prefix('t/{tenant:uuid}')->middleware(['web', 'resolve.tenant'])->group(function () {
    // Customer-facing homepage for this tenant
    Route::get('/', HomeController::class)->name('tenant.home');

    // Billboard browsing within tenant
    Route::get('/browse', [BillboardController::class, 'index'])->name('tenant.billboards.index');
    Route::get('/billboards/{billboard}', [BillboardController::class, 'show'])->name('tenant.billboards.show');

    // Tenant onboarding routes
    Route::prefix('onboarding')->middleware('auth')->group(function () {
        Route::get('/', [TenantOnboardingController::class, 'index'])->name('tenant.onboarding.index');
        Route::post('/welcome', [TenantOnboardingController::class, 'completeWelcome'])->name('tenant.onboarding.welcome');
        Route::post('/profile', [TenantOnboardingController::class, 'completeProfile'])->name('tenant.onboarding.profile');
        Route::post('/branding', [TenantOnboardingController::class, 'completeBranding'])->name('tenant.onboarding.branding');
        Route::post('/billboard', [TenantOnboardingController::class, 'createFirstBillboard'])->name('tenant.onboarding.billboard');
        Route::post('/client', [TenantOnboardingController::class, 'createFirstClient'])->name('tenant.onboarding.client');
        Route::post('/team', [TenantOnboardingController::class, 'inviteTeamMember'])->name('tenant.onboarding.team');
        Route::post('/payment', [TenantOnboardingController::class, 'completePaymentSetup'])->name('tenant.onboarding.payment');
        Route::post('/skip/{step}', [TenantOnboardingController::class, 'skipStep'])->name('tenant.onboarding.skip');
    });

    // Authenticated customer routes within tenant
    Route::middleware('auth')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('tenant.bookings.index');
        Route::get('/bookings/{booking:uuid}', [BookingController::class, 'show'])->name('tenant.bookings.show');
        Route::get('/dashboard', [BookingController::class, 'index'])->name('tenant.dashboard');
        Route::post('/bookings', [BookingController::class, 'store'])->name('tenant.bookings.store');
        Route::post('/bookings/quick', [BookingController::class, 'quickBook'])->name('tenant.bookings.quick');
    });

    // Tenant-specific admin panel (keeps data isolated)
    Route::prefix('admin')->group(function () {
        // Admin routes will be handled by Filament but scoped to this tenant
    });
});

// Global admin panel (for super-admin managing all tenants)
Route::prefix('super-admin')->group(function () {
    // This will be for managing tenants, not tenant-specific data
});

// Legacy routes for backward compatibility (will redirect to tenant-specific URLs)
Route::get('/', function() {
    // Redirect to main tenant or show tenant selection
    return redirect('/select-tenant');
})->name('home');

Route::get('/select-tenant', function() {
    return view('select-tenant');
})->name('select-tenant');

Route::get('/test', function () {
    return 'Basic routing works!';
});

// Admin panel remains accessible at /admin path
Route::redirect('/admin', '/admin/dashboard');
