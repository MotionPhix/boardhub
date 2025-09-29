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
// require __DIR__.'/admin.php'; // Temporarily disabled - conflicts with new system routes
require __DIR__.'/tenant.php'; // Enabled - now has proper tenant dashboard routes

// Include new role-based route files
require __DIR__.'/system.php';
// require __DIR__.'/tenant-admin.php'; // Temporarily disabled - missing controllers
// require __DIR__.'/member.php'; // Temporarily disabled

// Post-login redirection handling
use App\Http\Controllers\Auth\PostLoginRedirectController;

Route::get('/', function() {
    if (auth()->check()) {
        return app(PostLoginRedirectController::class)->handlePostLoginRedirect(auth()->user());
    }
    return redirect('/login');
})->name('home');

Route::get('/test', function () {
    return 'Basic routing works!';
});

// Test redirect logic
Route::get('/test-redirect', function () {
    if (!auth()->check()) {
        return 'User not logged in';
    }

    $user = auth()->user();
    $controller = app(\App\Http\Controllers\Auth\PostLoginRedirectController::class);

    try {
        $redirect = $controller->handlePostLoginRedirect($user);
        return [
            'user_id' => $user->id,
            'is_super_admin' => $user->isSuperAdmin(),
            'tenant_id' => $user->tenant_id,
            'redirect_url' => $redirect->getTargetUrl(),
        ];
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];
    }
})->middleware('auth');

// Debug current user
Route::get('/debug-user', function () {
    if (!auth()->check()) {
        return 'User not logged in';
    }

    $user = auth()->user();
    return [
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'tenant_id' => $user->tenant_id,
        'is_super_admin' => $user->isSuperAdmin(),
        'raw_tenant_id' => $user->getAttributes()['tenant_id'] ?? 'NOT_SET',
        'roles' => $user->roles->pluck('name'),
        'memberships' => $user->memberships->map(function($m) {
            return [
                'tenant_id' => $m->tenant_id,
                'role' => $m->role,
                'status' => $m->status,
                'tenant_name' => $m->tenant->name ?? 'Unknown'
            ];
        }),
        'should_access_system' => $user->isSuperAdmin(),
        'middleware_test' => 'If you see this, auth middleware passed'
    ];
})->middleware('auth');

// Test system access specifically
Route::get('/test-system-access', function () {
    if (!auth()->check()) {
        return 'User not logged in';
    }

    $user = auth()->user();
    $canAccess = $user->isSuperAdmin();

    return [
        'user_id' => $user->id,
        'email' => $user->email,
        'tenant_id' => $user->tenant_id,
        'is_super_admin' => $canAccess,
        'middleware_result' => $canAccess ? 'SHOULD_PASS' : 'SHOULD_FAIL',
        'test_timestamp' => now()->toISOString()
    ];
})->middleware(['auth', 'super.admin']);

// Organization routes have been moved to auth.php for better consolidation

// Checkout Routes for Paid Plans
Route::middleware(['auth'])->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/{plan}', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
    Route::post('/payment', [\App\Http\Controllers\CheckoutController::class, 'createPayment'])->name('payment');
    Route::post('/callback', [\App\Http\Controllers\CheckoutController::class, 'handleCallback'])->name('callback');
    Route::get('/success', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
    Route::get('/failure', [\App\Http\Controllers\CheckoutController::class, 'failure'])->name('failure');
});

// Webhook Routes (no auth required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/paychangu', [\App\Http\Controllers\WebhookController::class, 'paychangu'])->name('paychangu');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('health');
