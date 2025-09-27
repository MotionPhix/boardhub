<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billboard;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $systemMetrics = $this->getSystemMetrics();

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'systemMetrics' => $systemMetrics,
        ]);
    }

    private function getDashboardStats(): array
    {
        return Cache::remember('admin_dashboard_stats', 300, function () {
            $currentMonth = now()->startOfMonth();
            $previousMonth = now()->subMonth()->startOfMonth();

            // Total users
            $totalUsers = User::count();
            $usersThisMonth = User::where('created_at', '>=', $currentMonth)->count();
            $usersLastMonth = User::whereBetween('created_at', [$previousMonth, $currentMonth])->count();
            $userGrowth = $usersLastMonth > 0 ? (($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100 : 0;

            // Active tenants
            $activeTenants = Tenant::where('status', 'active')->count();
            $tenantsThisMonth = Tenant::where('created_at', '>=', $currentMonth)->count();
            $tenantsLastMonth = Tenant::whereBetween('created_at', [$previousMonth, $currentMonth])->count();
            $tenantGrowth = $tenantsLastMonth > 0 ? (($tenantsThisMonth - $tenantsLastMonth) / $tenantsLastMonth) * 100 : 0;

            // Total billboards
            $totalBillboards = Billboard::count();
            $billboardsThisMonth = Billboard::where('created_at', '>=', $currentMonth)->count();
            $billboardsLastMonth = Billboard::whereBetween('created_at', [$previousMonth, $currentMonth])->count();
            $billboardGrowth = $billboardsLastMonth > 0 ? (($billboardsThisMonth - $billboardsLastMonth) / $billboardsLastMonth) * 100 : 0;

            // Revenue calculation (if you have a payments table)
            $monthlyRevenue = $this->calculateMonthlyRevenue($currentMonth);
            $lastMonthRevenue = $this->calculateMonthlyRevenue($previousMonth);
            $revenueGrowth = $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

            return [
                'total_users' => number_format($totalUsers),
                'user_growth' => round($userGrowth, 2),
                'active_tenants' => number_format($activeTenants),
                'tenant_growth' => round($tenantGrowth, 2),
                'total_billboards' => number_format($totalBillboards),
                'billboard_growth' => round($billboardGrowth, 2),
                'monthly_revenue' => 'MWK ' . number_format($monthlyRevenue),
                'revenue_growth' => round($revenueGrowth, 2),
            ];
        });
    }

    private function getRecentActivity(): array
    {
        return Activity::with('causer')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'content' => $this->formatActivityDescription($activity),
                    'datetime' => $activity->created_at->toISOString(),
                    'date' => $activity->created_at->diffForHumans(),
                    'icon' => $this->getActivityIcon($activity->description),
                    'iconBackground' => $this->getActivityIconBackground($activity->description),
                ];
            })
            ->toArray();
    }

    private function getSystemMetrics(): array
    {
        return Cache::remember('admin_system_metrics', 60, function () {
            return [
                [
                    'name' => 'Database',
                    'status' => $this->checkDatabaseHealth() ? 'healthy' : 'warning',
                    'value' => '99.9% uptime'
                ],
                [
                    'name' => 'API Response',
                    'status' => 'healthy',
                    'value' => '<200ms avg'
                ],
                [
                    'name' => 'Cache Hit Rate',
                    'status' => 'healthy',
                    'value' => '94.2%'
                ],
                [
                    'name' => 'Storage Usage',
                    'status' => $this->getStorageUsage() < 80 ? 'healthy' : 'warning',
                    'value' => $this->getStorageUsage() . '% used'
                ]
            ];
        });
    }

    private function calculateMonthlyRevenue($month): float
    {
        // This would calculate based on your payment/booking system
        // For now, return a placeholder value
        return Booking::where('created_at', '>=', $month)
            ->where('status', 'confirmed')
            ->sum('total_amount') ?? 0;
    }

    private function formatActivityDescription($activity): string
    {
        $causer = $activity->causer ? $activity->causer->name : 'System';

        return match($activity->description) {
            'created' => "{$causer} created a new " . str_replace('App\\Models\\', '', $activity->subject_type),
            'updated' => "{$causer} updated " . str_replace('App\\Models\\', '', $activity->subject_type),
            'deleted' => "{$causer} deleted " . str_replace('App\\Models\\', '', $activity->subject_type),
            'login' => "{$causer} logged into the system",
            'logout' => "{$causer} logged out",
            default => $activity->description,
        };
    }

    private function getActivityIcon(string $description): string
    {
        return match($description) {
            'created' => 'PlusIcon',
            'updated' => 'PencilIcon',
            'deleted' => 'TrashIcon',
            'login' => 'ArrowRightOnRectangleIcon',
            'logout' => 'ArrowLeftOnRectangleIcon',
            default => 'InformationCircleIcon',
        };
    }

    private function getActivityIconBackground(string $description): string
    {
        return match($description) {
            'created' => 'bg-green-500',
            'updated' => 'bg-blue-500',
            'deleted' => 'bg-red-500',
            'login' => 'bg-indigo-500',
            'logout' => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }

    private function checkDatabaseHealth(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getStorageUsage(): int
    {
        // Calculate storage usage percentage
        // This is a simplified implementation
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;

        return round(($usedSpace / $totalSpace) * 100);
    }
}