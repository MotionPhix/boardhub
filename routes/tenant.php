<?php

use App\Http\Controllers\TenantController;
use App\Http\Controllers\Tenant\DashboardController;
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

        // Team invitation acceptance (public route)
        Route::get('/team/accept/{token}', [App\Http\Controllers\Tenant\TeamController::class, 'acceptInvitation'])->name('team.accept-invitation');

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

            // Tenant Dashboard (OOH advertising management)
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');

            // Customer Bookings Dashboard (separate from main tenant dashboard)
            Route::get('/bookings/dashboard', [BookingController::class, 'index'])->name('bookings.dashboard');

            // Booking Management
            Route::prefix('bookings')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::get('/{booking:uuid}', [BookingController::class, 'show'])->name('show');

                // Basic booking creation (available to all plans)
                Route::post('/', [BookingController::class, 'store'])->name('store');

                // Premium features require subscription checks
                Route::post('/quick', [BookingController::class, 'quickBook'])
                    ->middleware('subscription.feature:quick_booking')
                    ->name('quick');

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

            // Organization Onboarding
            Route::prefix('onboarding')->name('onboarding.')->group(function () {
                Route::get('/', [App\Http\Controllers\Tenant\OnboardingController::class, 'index'])->name('index');
                Route::get('/business-info', [App\Http\Controllers\Tenant\OnboardingController::class, 'businessInfo'])->name('business-info');
                Route::post('/business-info', [App\Http\Controllers\Tenant\OnboardingController::class, 'updateBusinessInfo'])->name('business-info.update');
                Route::get('/team-setup', [App\Http\Controllers\Tenant\OnboardingController::class, 'teamSetup'])->name('team-setup');
                Route::get('/branding', [App\Http\Controllers\Tenant\OnboardingController::class, 'branding'])->name('branding');
                Route::post('/branding', [App\Http\Controllers\Tenant\OnboardingController::class, 'updateBranding'])->name('branding.update');
                Route::get('/complete', [App\Http\Controllers\Tenant\OnboardingController::class, 'complete'])->name('complete');
                Route::post('/skip', [App\Http\Controllers\Tenant\OnboardingController::class, 'skip'])->name('skip');
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

                // Billboard Management
                Route::prefix('billboards')->name('billboards.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Tenant\BillboardController::class, 'index'])->name('index');
                    Route::get('/create', [App\Http\Controllers\Tenant\BillboardController::class, 'create'])->name('create');
                    Route::post('/', [App\Http\Controllers\Tenant\BillboardController::class, 'store'])->name('store');
                    Route::get('/{billboard}', [App\Http\Controllers\Tenant\BillboardController::class, 'show'])->name('show');
                    Route::get('/{billboard}/edit', [App\Http\Controllers\Tenant\BillboardController::class, 'edit'])->name('edit');
                    Route::put('/{billboard}', [App\Http\Controllers\Tenant\BillboardController::class, 'update'])->name('update');
                    Route::delete('/{billboard}', [App\Http\Controllers\Tenant\BillboardController::class, 'destroy'])->name('destroy');
                });

                // Booking Management
                Route::prefix('bookings')->name('bookings.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Tenant\BookingController::class, 'index'])->name('index');
                    Route::get('/{booking}', [App\Http\Controllers\Tenant\BookingController::class, 'show'])->name('show');
                    Route::post('/{booking}/approve', [App\Http\Controllers\Tenant\BookingController::class, 'approve'])->name('approve');
                    Route::post('/{booking}/reject', [App\Http\Controllers\Tenant\BookingController::class, 'reject'])->name('reject');
                    Route::post('/{booking}/cancel', [App\Http\Controllers\Tenant\BookingController::class, 'cancel'])->name('cancel');
                    Route::post('/bulk-action', [App\Http\Controllers\Tenant\BookingController::class, 'bulkAction'])->name('bulk-action');
                    Route::get('/analytics/overview', [App\Http\Controllers\Tenant\BookingController::class, 'analytics'])->name('analytics');
                });

                // Team Management
                Route::prefix('team')->name('team.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Tenant\TeamController::class, 'index'])->name('index');
                    Route::get('/{user}', [App\Http\Controllers\Tenant\TeamController::class, 'show'])->name('show');
                    Route::post('/invite', [App\Http\Controllers\Tenant\TeamController::class, 'invite'])->name('invite');
                    Route::put('/{user}/role', [App\Http\Controllers\Tenant\TeamController::class, 'updateRole'])->name('update-role');
                    Route::put('/{user}/status', [App\Http\Controllers\Tenant\TeamController::class, 'updateStatus'])->name('update-status');
                    Route::delete('/{user}', [App\Http\Controllers\Tenant\TeamController::class, 'removeMember'])->name('remove');
                    Route::post('/invitations/{invitation}/resend', [App\Http\Controllers\Tenant\TeamController::class, 'resendInvitation'])->name('resend-invitation');
                    Route::delete('/invitations/{invitation}', [App\Http\Controllers\Tenant\TeamController::class, 'cancelInvitation'])->name('cancel-invitation');
                    Route::get('/permissions/overview', [App\Http\Controllers\Tenant\TeamController::class, 'permissions'])->name('permissions');
                });

                // Analytics & Reports
                Route::prefix('analytics')->name('analytics.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Tenant\AnalyticsController::class, 'index'])->name('index');
                    Route::get('/revenue', [App\Http\Controllers\Tenant\AnalyticsController::class, 'revenue'])->name('revenue');
                    Route::get('/billboards', [App\Http\Controllers\Tenant\AnalyticsController::class, 'billboards'])->name('billboards');
                    Route::get('/bookings', [App\Http\Controllers\Tenant\AnalyticsController::class, 'bookings'])->name('bookings');
                    Route::get('/customers', [App\Http\Controllers\Tenant\AnalyticsController::class, 'customers'])->name('customers');
                    Route::post('/export', [App\Http\Controllers\Tenant\AnalyticsController::class, 'export'])->name('export');
                });

                // Settings Management
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Tenant\SettingsController::class, 'index'])->name('index');
                    Route::match(['get', 'post'], '/general', [App\Http\Controllers\Tenant\SettingsController::class, 'general'])->name('general');
                    Route::match(['get', 'post'], '/branding', [App\Http\Controllers\Tenant\SettingsController::class, 'branding'])->name('branding');
                    Route::get('/billing', [App\Http\Controllers\Tenant\SettingsController::class, 'billing'])->name('billing');
                    Route::match(['get', 'post'], '/integrations', [App\Http\Controllers\Tenant\SettingsController::class, 'integrations'])->name('integrations');
                    Route::match(['get', 'post'], '/notifications', [App\Http\Controllers\Tenant\SettingsController::class, 'notifications'])->name('notifications');
                    Route::match(['get', 'post'], '/security', [App\Http\Controllers\Tenant\SettingsController::class, 'security'])->name('security');
                });
            });
    });