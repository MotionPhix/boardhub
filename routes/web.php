<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Include organized route files
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';

// Legacy redirects for backward compatibility
Route::get('/', function() {
    if (auth()->check()) {
        return redirect()->route('tenants.select');
    }
    return redirect('/login');
})->name('home');

Route::get('/test', function () {
    return 'Basic routing works!';
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('health');
