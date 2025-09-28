<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // System admin dashboard - overview of entire system
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'total_users' => User::count(),
            'super_admins' => User::whereNull('tenant_id')->count(),
        ];

        $recent_tenants = Tenant::latest()
            ->take(5)
            ->get(['id', 'name', 'slug', 'is_active', 'created_at']);

        $recent_users = User::latest()
            ->take(10)
            ->get(['id', 'name', 'email', 'tenant_id', 'created_at']);

        // Chart data for visualizations
        $chartData = [
            'tenant_growth' => $this->getTenantGrowthData(),
            'user_distribution' => $this->getUserDistributionData(),
            'activity_overview' => $this->getActivityOverviewData(),
        ];

        return Inertia::render('system/Dashboard', [
            'stats' => $stats,
            'recent_tenants' => $recent_tenants,
            'recent_users' => $recent_users,
            'chartData' => $chartData,
        ]);
    }

    private function getTenantGrowthData(): array
    {
        $months = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $data[] = Tenant::whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          ->count();
        }

        return [
            'categories' => $months,
            'series' => [
                [
                    'name' => 'New Tenants',
                    'data' => $data
                ]
            ]
        ];
    }

    private function getUserDistributionData(): array
    {
        $superAdmins = User::whereNull('tenant_id')->count();
        $tenantUsers = User::whereNotNull('tenant_id')->count();

        return [
            'series' => [$superAdmins, $tenantUsers],
            'labels' => ['Super Admins', 'Tenant Users']
        ];
    }

    private function getActivityOverviewData(): array
    {
        $days = [];
        $tenantData = [];
        $userData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date->format('M j');

            $tenantData[] = Tenant::whereDate('created_at', $date->toDateString())->count();
            $userData[] = User::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'categories' => $days,
            'series' => [
                [
                    'name' => 'New Tenants',
                    'data' => $tenantData
                ],
                [
                    'name' => 'New Users',
                    'data' => $userData
                ]
            ]
        ];
    }
}