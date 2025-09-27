<?php

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TenantApiController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\BillboardDiscoveryController;
use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Secure API routes with rate limiting and authentication.
| Includes webhooks, health checks, and external integrations.
|
*/

// Public API Routes (with rate limiting)
Route::middleware(['api', 'throttle:60,1'])->group(function () {

    // Health Check Endpoints
    Route::prefix('health')->name('api.health.')->group(function () {
        Route::get('/', [HealthCheckController::class, 'basic'])->name('basic');
        Route::get('/detailed', [HealthCheckController::class, 'detailed'])->name('detailed');
        Route::get('/database', [HealthCheckController::class, 'database'])->name('database');
        Route::get('/cache', [HealthCheckController::class, 'cache'])->name('cache');
        Route::get('/storage', [HealthCheckController::class, 'storage'])->name('storage');
    });

    // System Status (Public)
    Route::get('/status', [HealthCheckController::class, 'systemStatus'])->name('api.status');

    // Legacy search endpoint
    Route::get('/search', SearchController::class)->name('api.search');

    // Webhook Endpoints (with signature verification)
    Route::prefix('webhooks')->name('api.webhooks.')->group(function () {

        // PayChangu Payment Webhooks
        Route::post('/payments/paychangu', [WebhookController::class, 'paychanguPayment'])
            ->name('payments.paychangu');

        // Generic payment webhook handler
        Route::post('/payments/{provider}', [WebhookController::class, 'paymentWebhook'])
            ->name('payments.provider')
            ->where('provider', 'paychangu|airtel|tnm');

        // System webhooks (for monitoring services)
        Route::post('/system/alerts', [WebhookController::class, 'systemAlert'])
            ->name('system.alerts');
    });
});

// Authentication API Routes
Route::prefix('auth')->name('api.auth.')->middleware(['api', 'throttle:10,1'])->group(function () {

    // API Token Authentication
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');

    // Authenticated API routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');
        Route::post('/revoke-tokens', [AuthController::class, 'revokeTokens'])->name('revoke-tokens');
    });
});

// Authenticated API Routes
Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {

    // User API
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::get('/tenants', [TenantApiController::class, 'userTenants'])->name('tenants');
        Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    });

    // Customer-facing API routes
    Route::post('/bookings', [BookingController::class, 'store'])->name('api.bookings.store');
    Route::post('/bookings/quick', [BookingController::class, 'quickBook'])->name('api.bookings.quick');
});

// Tenant-scoped API routes
Route::prefix('t/{tenant:uuid}')->middleware(['resolve.tenant'])->group(function () {
    // AI-Powered Billboard Discovery
    Route::prefix('billboards')->group(function () {
        Route::post('/discover', [BillboardDiscoveryController::class, 'discover'])
            ->name('api.tenant.billboards.discover');

        Route::get('/search-location', [BillboardDiscoveryController::class, 'searchByLocation'])
            ->name('api.tenant.billboards.search-location');

        Route::post('/check-availability', [BillboardDiscoveryController::class, 'checkAvailability'])
            ->name('api.tenant.billboards.check-availability');

        Route::get('/recommendations/{clientId}', [BillboardDiscoveryController::class, 'getPersonalizedRecommendations'])
            ->name('api.tenant.billboards.recommendations');

        Route::get('/suggestions', [BillboardDiscoveryController::class, 'getSuggestions'])
            ->name('api.tenant.billboards.suggestions');

        Route::get('/trending', [BillboardDiscoveryController::class, 'getTrending'])
            ->name('api.tenant.billboards.trending');

        Route::post('/advanced-search', [BillboardDiscoveryController::class, 'advancedSearch'])
            ->name('api.tenant.billboards.advanced-search');
    });

    // Dynamic Pricing Engine
    Route::prefix('pricing')->group(function () {
        Route::get('/billboard/{billboardId}', [App\Http\Controllers\Api\DynamicPricingController::class, 'getBillboardPricing'])
            ->name('api.tenant.pricing.billboard');

        Route::post('/bulk', [App\Http\Controllers\Api\DynamicPricingController::class, 'getBulkPricing'])
            ->name('api.tenant.pricing.bulk');

        Route::get('/analytics', [App\Http\Controllers\Api\DynamicPricingController::class, 'getPricingAnalytics'])
            ->name('api.tenant.pricing.analytics');

        Route::get('/market-insights', [App\Http\Controllers\Api\DynamicPricingController::class, 'getMarketInsights'])
            ->name('api.tenant.pricing.market-insights');

        Route::put('/billboard/{billboardId}/apply', [App\Http\Controllers\Api\DynamicPricingController::class, 'applyPricingRecommendation'])
            ->name('api.tenant.pricing.apply');
    });

    // Mobile Money Payment System
    Route::prefix('payments')->group(function () {
        Route::get('/providers', [App\Http\Controllers\Api\PaymentController::class, 'getProviders'])
            ->name('api.tenant.payments.providers');

        Route::post('/process', [App\Http\Controllers\Api\PaymentController::class, 'processPayment'])
            ->name('api.tenant.payments.process');

        Route::get('/{paymentUuid}/status', [App\Http\Controllers\Api\PaymentController::class, 'checkStatus'])
            ->name('api.tenant.payments.status');

        Route::get('/history', [App\Http\Controllers\Api\PaymentController::class, 'getHistory'])
            ->name('api.tenant.payments.history');

        Route::get('/analytics', [App\Http\Controllers\Api\PaymentController::class, 'getAnalytics'])
            ->name('api.tenant.payments.analytics');

        Route::post('/{paymentUuid}/retry', [App\Http\Controllers\Api\PaymentController::class, 'retryPayment'])
            ->name('api.tenant.payments.retry');

        Route::put('/{paymentUuid}/cancel', [App\Http\Controllers\Api\PaymentController::class, 'cancelPayment'])
            ->name('api.tenant.payments.cancel');
    });
});
