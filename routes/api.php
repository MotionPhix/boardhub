<?php

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\BillboardDiscoveryController;
use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/search', SearchController::class)->name('api.search');

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

// Payment webhooks (outside tenant scope)
Route::prefix('webhooks/payments')->group(function () {
    Route::post('/{provider}', [App\Http\Controllers\Api\PaymentController::class, 'handleWebhook'])
        ->name('api.payments.webhook');

    // PayChangu specific webhook endpoint
    Route::post('/paychangu', [App\Http\Controllers\Api\PaymentController::class, 'handleWebhook'])
        ->name('api.payments.webhook.paychangu');
});

// Customer-facing API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store'])->name('api.bookings.store');
    Route::post('/bookings/quick', [BookingController::class, 'quickBook'])->name('api.bookings.quick');
});
