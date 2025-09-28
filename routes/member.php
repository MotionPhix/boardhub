<?php

use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\BillboardController;
use App\Http\Controllers\Member\BookingController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member Routes
|--------------------------------------------------------------------------
|
| Routes for regular tenant members.
| These users can browse billboards, create bookings, and manage their profile.
|
*/

Route::prefix('tenant/{tenant}/member')
    ->name('member.')
    ->middleware(['web', 'auth', 'resolve.tenant', 'tenant.member'])
    ->group(function () {

        // Member Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Browse Billboards
        Route::prefix('billboards')->name('billboards.')->group(function () {
            Route::get('/', [BillboardController::class, 'index'])->name('index');
            Route::get('/{billboard}', [BillboardController::class, 'show'])->name('show');
            Route::get('/{billboard}/availability', [BillboardController::class, 'checkAvailability'])->name('availability');
        });

        // My Bookings
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('index');
            Route::get('/create', [BookingController::class, 'create'])->name('create');
            Route::post('/', [BookingController::class, 'store'])->name('store');
            Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
            Route::get('/{booking}/edit', [BookingController::class, 'edit'])->name('edit');
            Route::put('/{booking}', [BookingController::class, 'update'])->name('update');
            Route::delete('/{booking}', [BookingController::class, 'cancel'])->name('cancel');

            // Booking Actions (limited for members)
            Route::get('/{booking}/receipt', [BookingController::class, 'downloadReceipt'])->name('receipt');
            Route::post('/{booking}/extend', [BookingController::class, 'requestExtension'])->name('extend');
            Route::post('/{booking}/modify', [BookingController::class, 'requestModification'])->name('modify');
        });

        // Member Profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::get('/preferences', [ProfileController::class, 'preferences'])->name('preferences');
            Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
            Route::get('/notifications', [ProfileController::class, 'notifications'])->name('notifications');
            Route::put('/notifications', [ProfileController::class, 'updateNotifications'])->name('notifications.update');
        });

        // Member Activity & History
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('/', [ProfileController::class, 'activity'])->name('index');
            Route::get('/bookings-history', [BookingController::class, 'history'])->name('bookings-history');
            Route::get('/payment-history', [ProfileController::class, 'paymentHistory'])->name('payment-history');
        });

        // Member Support
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [ProfileController::class, 'support'])->name('index');
            Route::get('/tickets', [ProfileController::class, 'tickets'])->name('tickets');
            Route::post('/tickets', [ProfileController::class, 'createTicket'])->name('tickets.create');
            Route::get('/tickets/{ticket}', [ProfileController::class, 'showTicket'])->name('tickets.show');
            Route::get('/faq', [ProfileController::class, 'faq'])->name('faq');
        });
    });