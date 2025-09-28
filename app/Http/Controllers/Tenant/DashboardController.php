<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Billboard;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        // Core OOH advertising metrics for dashboard
        $stats = $this->getCoreMetrics($tenant);
        $billboardMetrics = $this->getBillboardMetrics($tenant);
        $recentActivity = $this->getRecentActivity($tenant);
        $revenueAnalytics = $this->getRevenueAnalytics($tenant);
        $expiringContracts = $this->getExpiringContracts($tenant);
        $topPerformingBillboards = $this->getTopPerformingBillboards($tenant);

        return Inertia::render('tenant/Dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'billboardMetrics' => $billboardMetrics,
            'recentActivity' => $recentActivity,
            'revenueAnalytics' => $revenueAnalytics,
            'expiringContracts' => $expiringContracts,
            'topPerformingBillboards' => $topPerformingBillboards,
        ]);
    }

    /**
     * Core OOH advertising metrics
     */
    private function getCoreMetrics($tenant): array
    {
        $totalBillboards = Billboard::where('tenant_id', $tenant->id)->count();
        $availableBillboards = Billboard::where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->count();

        $activeBookings = Booking::where('tenant_id', $tenant->id)
            ->where('status', 'confirmed')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();

        $totalRevenue = Booking::where('tenant_id', $tenant->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('final_price');

        $monthlyRevenue = Booking::where('tenant_id', $tenant->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('final_price');

        $utilizationRate = $totalBillboards > 0
            ? round((($totalBillboards - $availableBillboards) / $totalBillboards) * 100, 1)
            : 0;

        return [
            'total_billboards' => $totalBillboards,
            'available_billboards' => $availableBillboards,
            'active_bookings' => $activeBookings,
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'utilization_rate' => $utilizationRate,
            'team_members' => $tenant->memberships()->active()->count(),
        ];
    }

    /**
     * Billboard utilization and performance metrics
     */
    private function getBillboardMetrics($tenant): array
    {
        $billboardsByLocation = Billboard::where('tenant_id', $tenant->id)
            ->select('location', DB::raw('count(*) as count'))
            ->groupBy('location')
            ->get();

        $billboardsBySize = Billboard::where('tenant_id', $tenant->id)
            ->select('size', DB::raw('count(*) as count'))
            ->groupBy('size')
            ->get();

        $utilizationTrend = DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(bookings.created_at) as date, COUNT(*) as bookings')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'by_location' => $billboardsByLocation,
            'by_size' => $billboardsBySize,
            'utilization_trend' => $utilizationTrend,
        ];
    }

    /**
     * Recent activity for real-time dashboard updates
     */
    private function getRecentActivity($tenant): array
    {
        $recentBookings = Booking::where('tenant_id', $tenant->id)
            ->with(['billboard', 'client'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'billboard_name' => $booking->billboard->name ?? 'N/A',
                    'billboard_location' => $booking->billboard->location ?? 'N/A',
                    'client_name' => $booking->client->name ?? 'N/A',
                    'amount' => $booking->final_price ?? $booking->requested_price,
                    'status' => $booking->status,
                    'start_date' => $booking->start_date?->format('M d, Y'),
                    'end_date' => $booking->end_date?->format('M d, Y'),
                    'created_at' => $booking->created_at->format('M d, Y g:i A'),
                ];
            });

        $recentInquiries = Booking::where('tenant_id', $tenant->id)
            ->where('status', 'requested')
            ->with(['billboard', 'client'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'billboard_name' => $booking->billboard->name ?? 'N/A',
                    'client_name' => $booking->client->name ?? 'N/A',
                    'amount' => $booking->requested_price,
                    'created_at' => $booking->created_at->format('M d, Y g:i A'),
                ];
            });

        return [
            'recent_bookings' => $recentBookings,
            'recent_inquiries' => $recentInquiries,
        ];
    }

    /**
     * Revenue analytics for charts
     */
    private function getRevenueAnalytics($tenant): array
    {
        // Monthly revenue for the last 12 months
        $monthlyRevenue = DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', now()->subMonths(12))
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('YEAR(bookings.created_at) as year, MONTH(bookings.created_at) as month, SUM(bookings.final_price) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::create($item->year, $item->month)->format('M Y'),
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Revenue by billboard type/size
        $revenueBySize = DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('billboards.size, SUM(bookings.final_price) as revenue')
            ->groupBy('billboards.size')
            ->get();

        return [
            'monthly_revenue' => $monthlyRevenue,
            'revenue_by_size' => $revenueBySize,
        ];
    }

    /**
     * Expiring contracts - critical for OOH business
     */
    private function getExpiringContracts($tenant): array
    {
        $expiringIn7Days = Booking::where('tenant_id', $tenant->id)
            ->where('status', 'confirmed')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(7))
            ->with(['billboard', 'client'])
            ->orderBy('end_date')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'billboard_name' => $booking->billboard->name ?? 'N/A',
                    'billboard_location' => $booking->billboard->location ?? 'N/A',
                    'client_name' => $booking->client->name ?? 'N/A',
                    'amount' => $booking->final_price ?? $booking->requested_price,
                    'end_date' => $booking->end_date?->format('M d, Y'),
                    'days_remaining' => now()->diffInDays($booking->end_date),
                ];
            });

        $expiringIn30Days = Booking::where('tenant_id', $tenant->id)
            ->where('status', 'confirmed')
            ->where('end_date', '>', now()->addDays(7))
            ->where('end_date', '<=', now()->addDays(30))
            ->count();

        return [
            'expiring_7_days' => $expiringIn7Days,
            'expiring_30_days_count' => $expiringIn30Days,
        ];
    }

    /**
     * Top performing billboards by revenue
     */
    private function getTopPerformingBillboards($tenant): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->where('bookings.created_at', '>=', now()->subMonths(6))
            ->selectRaw('
                billboards.id,
                billboards.name,
                billboards.location,
                billboards.size,
                SUM(bookings.final_price) as total_revenue,
                COUNT(bookings.id) as total_bookings,
                AVG(bookings.final_price) as avg_booking_value
            ')
            ->groupBy('billboards.id', 'billboards.name', 'billboards.location', 'billboards.size')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get()
            ->map(function ($billboard) {
                return [
                    'id' => $billboard->id,
                    'name' => $billboard->name,
                    'location' => $billboard->location,
                    'size' => $billboard->size,
                    'total_revenue' => (float) $billboard->total_revenue,
                    'total_bookings' => $billboard->total_bookings,
                    'avg_booking_value' => round((float) $billboard->avg_booking_value, 2),
                ];
            })
            ->toArray();
    }

    /**
     * API endpoint for chart data (called via AJAX)
     */
    public function chartData(Request $request)
    {
        $tenant = app('tenant');
        $type = $request->get('type');

        switch ($type) {
            case 'revenue':
                return response()->json($this->getRevenueAnalytics($tenant));
            case 'utilization':
                return response()->json($this->getBillboardMetrics($tenant));
            case 'performance':
                return response()->json($this->getTopPerformingBillboards($tenant));
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }
}