<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $overall = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 'ok' : 'error';

        return response()->json([
            'status' => $overall,
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ]);
    }

    public function database(): JsonResponse
    {
        $check = $this->checkDatabase();

        return response()->json([
            'status' => $check['status'],
            'timestamp' => now()->toISOString(),
            'details' => $check,
        ]);
    }

    public function cache(): JsonResponse
    {
        $check = $this->checkCache();

        return response()->json([
            'status' => $check['status'],
            'timestamp' => now()->toISOString(),
            'details' => $check,
        ]);
    }

    public function storage(): JsonResponse
    {
        $check = $this->checkStorage();

        return response()->json([
            'status' => $check['status'],
            'timestamp' => now()->toISOString(),
            'details' => $check,
        ]);
    }

    public function systemStatus(): JsonResponse
    {
        $status = [
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'system' => $status,
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'connection_time_ms' => $connectionTime,
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . now()->timestamp;
            $value = 'test_value';

            $start = microtime(true);
            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $value) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $responseTime,
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache write/read test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_' . now()->timestamp . '.txt';
            $testContent = 'Health check test';

            $start = microtime(true);
            $disk->put($testFile, $testContent);
            $retrieved = $disk->get($testFile);
            $disk->delete($testFile);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $responseTime,
                    'disk' => 'local',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Storage write/read test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}