<?php

use App\Http\Controllers\TenantController;
use App\Http\Controllers\BillboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TenantOnboardingController;
use App\Http\Controllers\Api\BillboardDiscoveryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DynamicPricingController;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\TenantAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Multi-tenant routes with UUID parameter for security.
| All routes are scoped to specific tenants.
|
*/

Route::prefix('t/{tenant:uuid}')
    ->name('tenant.')
    ->middleware(['web', ResolveTenant::class, TenantAccess::class])
    ->group(function () {

        // Public tenant pages (no authentication required)
        Route::get('/', HomeController::class)->name('home');
        Route::get('/browse', [BillboardController::class, 'index'])->name('billboards.index');
        Route::get('/billboards/{billboard}', [BillboardController::class, 'show'])->name('billboards.show');

        // Public API endpoints for billboard discovery
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/billboards/search-location', [BillboardDiscoveryController::class, 'searchLocation'])
                ->name('billboards.search-location');
            Route::get('/billboards/suggestions', [BillboardDiscoveryController::class, 'getSuggestions'])
                ->name('billboards.suggestions');
            Route::get('/billboards/trending', [BillboardDiscoveryController::class, 'getTrending'])
                ->name('billboards.trending');
            Route::post('/billboards/discover', [BillboardDiscoveryController::class, 'discover'])
                ->name('billboards.discover');
            Route::post('/billboards/advanced-search', [BillboardDiscoveryController::class, 'advancedSearch'])
                ->name('billboards.advanced-search');
            Route::post('/billboards/check-availability', [BillboardDiscoveryController::class, 'checkAvailability'])
                ->name('billboards.check-availability');
            Route::get('/billboards/recommendations/{clientId}', [BillboardDiscoveryController::class, 'getRecommendations'])
                ->name('billboards.recommendations');
        });

        // Tenant onboarding routes (authenticated)
        Route::prefix('onboarding')
            ->name('onboarding.')
            ->middleware('auth')
            ->group(function () {
                Route::get('/', [TenantOnboardingController::class, 'index'])->name('index');
                Route::post('/welcome', [TenantOnboardingController::class, 'completeWelcome'])->name('welcome');
                Route::post('/profile', [TenantOnboardingController::class, 'completeProfile'])->name('profile');
                Route::post('/branding', [TenantOnboardingController::class, 'completeBranding'])->name('branding');
                Route::post('/billboard', [TenantOnboardingController::class, 'createFirstBillboard'])->name('billboard');
                Route::post('/client', [TenantOnboardingController::class, 'createFirstClient'])->name('client');
                Route::post('/team', [TenantOnboardingController::class, 'inviteTeamMember'])->name('team');
                Route::post('/payment', [TenantOnboardingController::class, 'completePaymentSetup'])->name('payment');
                Route::post('/skip/{step}', [TenantOnboardingController::class, 'skipStep'])->name('skip');
            });

        // Authenticated customer routes within tenant
        Route::middleware('auth')->group(function () {

            // Customer Dashboard
            Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');

            // Booking Management
            Route::prefix('bookings')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::get('/{booking:uuid}', [BookingController::class, 'show'])->name('show');
                Route::post('/', [BookingController::class, 'store'])->name('store');
                Route::post('/quick', [BookingController::class, 'quickBook'])->name('quick');
                Route::put('/{booking}', [BookingController::class, 'update'])->name('update');
                Route::delete('/{booking}', [BookingController::class, 'cancel'])->name('cancel');
            });

            // Customer Profile within Tenant
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [TenantController::class, 'profile'])->name('index');
                Route::put('/', [TenantController::class, 'updateProfile'])->name('update');
                Route::get('/preferences', [TenantController::class, 'preferences'])->name('preferences');
                Route::put('/preferences', [TenantController::class, 'updatePreferences'])->name('preferences.update');
            });
        });

        // Authenticated API routes (for AJAX/SPA interactions)
        Route::prefix('api')
            ->name('api.')
            ->middleware('auth')
            ->group(function () {

                // Payment Processing
                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::get('/providers', [PaymentController::class, 'getProviders'])->name('providers');
                    Route::post('/process', [PaymentController::class, 'processPayment'])->name('process');
                    Route::get('/{paymentUuid}/status', [PaymentController::class, 'checkStatus'])->name('status');
                    Route::post('/{paymentUuid}/retry', [PaymentController::class, 'retryPayment'])->name('retry');
                    Route::put('/{paymentUuid}/cancel', [PaymentController::class, 'cancelPayment'])->name('cancel');
                    Route::get('/history', [PaymentController::class, 'getHistory'])->name('history');
                    Route::get('/analytics', [PaymentController::class, 'getAnalytics'])->name('analytics');
                });

                // Dynamic Pricing
                Route::prefix('pricing')->name('pricing.')->group(function () {
                    Route::get('/billboard/{billboardId}', [DynamicPricingController::class, 'getBillboardPricing'])
                        ->name('billboard');
                    Route::post('/bulk', [DynamicPricingController::class, 'getBulkPricing'])->name('bulk');
                    Route::put('/billboard/{billboardId}/apply', [DynamicPricingController::class, 'applyPricing'])
                        ->name('apply');
                    Route::get('/market-insights', [DynamicPricingController::class, 'getMarketInsights'])
                        ->name('market-insights');
                    Route::get('/analytics', [DynamicPricingController::class, 'getPricingAnalytics'])
                        ->name('analytics');
                });
            });

        // Tenant Management Routes (for tenant owners/admins)
        Route::prefix('manage')
            ->name('manage.')
            ->middleware(['auth', 'can:tenant-admin'])
            ->group(function () {

                Route::get('/', [TenantController::class, 'dashboard'])->name('dashboard');

                // Tenant Settings
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [TenantController::class, 'settings'])->name('index');
                    Route::put('/', [TenantController::class, 'updateSettings'])->name('update');
                    Route::get('/branding', [TenantController::class, 'branding'])->name('branding');
                    Route::put('/branding', [TenantController::class, 'updateBranding'])->name('branding.update');
                    Route::get('/integrations', [TenantController::class, 'integrations'])->name('integrations');
                    Route::put('/integrations', [TenantController::class, 'updateIntegrations'])->name('integrations.update');
                });

                // Team Management
                Route::prefix('team')->name('team.')->group(function () {
                    Route::get('/', [TenantController::class, 'team'])->name('index');
                    Route::post('/invite', [TenantController::class, 'inviteTeamMember'])->name('invite');
                    Route::put('/member/{user}', [TenantController::class, 'updateTeamMember'])->name('update');
                    Route::delete('/member/{user}', [TenantController::class, 'removeTeamMember'])->name('remove');
                    Route::post('/resend-invitation/{invitation}', [TenantController::class, 'resendInvitation'])->name('resend-invitation');
                });

                // Analytics & Reports
                Route::prefix('analytics')->name('analytics.')->group(function () {
                    Route::get('/', [TenantController::class, 'analytics'])->name('index');
                    Route::get('/bookings', [TenantController::class, 'bookingAnalytics'])->name('bookings');
                    Route::get('/revenue', [TenantController::class, 'revenueAnalytics'])->name('revenue');
                    Route::get('/performance', [TenantController::class, 'performanceAnalytics'])->name('performance');
                    Route::post('/export', [TenantController::class, 'exportAnalytics'])->name('export');
                });
            });
    });