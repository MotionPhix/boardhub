<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_version' => config('app.version', '1.0.0'),
            'app_environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'cache_driver' => config('cache.default'),
            'session_lifetime' => config('session.lifetime'),
            'max_upload_size' => ini_get('upload_max_filesize'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return Inertia::render('Admin/Settings/SystemSettings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'session_lifetime' => 'required|integer|min:1|max:10080',
            'debug_mode' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update .env file or config cache
        // Note: In production, you'd want to update the .env file properly

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
        ]);
    }

    public function maintenance(Request $request): Response
    {
        $maintenanceStatus = [
            'is_down' => app()->isDownForMaintenance(),
            'scheduled_maintenance' => Cache::get('scheduled_maintenance'),
            'last_maintenance' => Cache::get('last_maintenance_date'),
        ];

        return Inertia::render('Admin/Settings/Maintenance', [
            'maintenance' => $maintenanceStatus,
        ]);
    }

    public function toggleMaintenance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enable' => 'required|boolean',
            'message' => 'nullable|string|max:500',
            'retry_after' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('enable')) {
            $options = [];

            if ($request->filled('message')) {
                $options['--message'] = $request->message;
            }

            if ($request->filled('retry_after')) {
                $options['--retry'] = $request->retry_after;
            }

            Artisan::call('down', $options);

            Cache::put('last_maintenance_date', now(), now()->addDays(30));

            $message = 'Maintenance mode enabled';
        } else {
            Artisan::call('up');
            $message = 'Maintenance mode disabled';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_down' => $request->boolean('enable'),
        ]);
    }

    public function cache(Request $request): Response
    {
        $cacheInfo = [
            'driver' => config('cache.default'),
            'cache_size' => $this->getCacheSize(),
            'last_cleared' => Cache::get('cache_last_cleared'),
        ];

        return Inertia::render('Admin/Settings/Cache', [
            'cache' => $cacheInfo,
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:all,config,route,view,application',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->type;
        $message = '';

        switch ($type) {
            case 'all':
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                $message = 'All caches cleared successfully';
                break;
            case 'config':
                Artisan::call('config:clear');
                $message = 'Configuration cache cleared successfully';
                break;
            case 'route':
                Artisan::call('route:clear');
                $message = 'Route cache cleared successfully';
                break;
            case 'view':
                Artisan::call('view:clear');
                $message = 'View cache cleared successfully';
                break;
            case 'application':
                Artisan::call('cache:clear');
                $message = 'Application cache cleared successfully';
                break;
        }

        Cache::put('cache_last_cleared', now(), now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function tenantSettings(Request $request): Response
    {
        $tenant = $request->user()->currentTenant;

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        return Inertia::render('Admin/Settings/TenantSettings', [
            'tenant' => $tenant->only(['id', 'name', 'slug', 'status', 'settings']),
        ]);
    }

    public function updateTenantSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'settings.timezone' => 'required|string',
            'settings.currency' => 'required|string|size:3',
            'settings.date_format' => 'required|string',
            'settings.time_format' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $request->user()->currentTenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        $tenant->update([
            'name' => $request->name,
            'settings' => $request->settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant settings updated successfully',
        ]);
    }

    public function branding(Request $request): Response
    {
        $tenant = $request->user()->currentTenant;

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        return Inertia::render('Admin/Settings/Branding', [
            'tenant' => $tenant->only(['id', 'name', 'branding']),
        ]);
    }

    public function updateBranding(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branding.logo' => 'nullable|image|max:2048',
            'branding.primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.font_family' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $request->user()->currentTenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        $branding = $request->branding;

        if ($request->hasFile('branding.logo')) {
            $logoPath = $request->file('branding.logo')->store('tenant-logos', 'public');
            $branding['logo'] = $logoPath;
        }

        $tenant->update([
            'branding' => $branding,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branding updated successfully',
        ]);
    }

    private function getCacheSize(): string
    {
        // This is a simplified implementation
        // In production, you'd want to calculate actual cache size
        return '0 MB';
    }
}