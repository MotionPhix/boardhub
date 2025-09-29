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

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $overview = $this->getOverviewMetrics($tenant, $startDate);
        $revenueAnalytics = $this->getRevenueAnalytics($tenant, $startDate);
        $utilizationMetrics = $this->getUtilizationMetrics($tenant, $startDate);
        $performanceMetrics = $this->getPerformanceMetrics($tenant, $startDate);

        return Inertia::render('tenant/analytics/Index', [
            'tenant' => $tenant,
            'period' => $period,
            'overview' => $overview,
            'revenueAnalytics' => $revenueAnalytics,
            'utilizationMetrics' => $utilizationMetrics,
            'performanceMetrics' => $performanceMetrics
        ]);
    }

    public function revenue(Request $request)
    {
        $tenant = app('tenant');
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $revenueData = [
            'total_revenue' => $this->getTotalRevenue($tenant, $startDate),
            'monthly_revenue' => $this->getMonthlyRevenue($tenant),
            'revenue_by_billboard' => $this->getRevenueByBillboard($tenant, $startDate),
            'revenue_by_size' => $this->getRevenueBySizeCategory($tenant, $startDate),
            'revenue_by_location' => $this->getRevenueByLocation($tenant, $startDate),
            'revenue_growth' => $this->getRevenueGrowth($tenant),
            'average_booking_value' => $this->getAverageBookingValue($tenant, $startDate),
            'revenue_forecast' => $this->getRevenueForecast($tenant)
        ];

        return Inertia::render('tenant/analytics/Revenue', [
            'tenant' => $tenant,
            'period' => $period,
            'data' => $revenueData
        ]);
    }

    public function billboards(Request $request)
    {
        $tenant = app('tenant');
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $billboardData = [
            'total_billboards' => Billboard::where('tenant_id', $tenant->id)->count(),
            'utilization_rates' => $this->getBillboardUtilizationRates($tenant, $startDate),
            'performance_ranking' => $this->getBillboardPerformanceRanking($tenant, $startDate),
            'size_distribution' => $this->getBillboardSizeDistribution($tenant),
            'location_performance' => $this->getLocationPerformance($tenant, $startDate),
            'occupancy_calendar' => $this->getOccupancyCalendar($tenant),
            'availability_trends' => $this->getAvailabilityTrends($tenant, $startDate)
        ];

        return Inertia::render('tenant/analytics/Billboards', [
            'tenant' => $tenant,
            'period' => $period,
            'data' => $billboardData
        ]);
    }

    public function bookings(Request $request)
    {
        $tenant = app('tenant');
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $bookingData = [
            'booking_trends' => $this->getBookingTrends($tenant, $startDate),
            'conversion_funnel' => $this->getConversionFunnel($tenant, $startDate),
            'booking_sources' => $this->getBookingSources($tenant, $startDate),
            'cancellation_analysis' => $this->getCancellationAnalysis($tenant, $startDate),
            'peak_booking_times' => $this->getPeakBookingTimes($tenant, $startDate),
            'booking_duration_analysis' => $this->getBookingDurationAnalysis($tenant, $startDate),
            'repeat_customer_rate' => $this->getRepeatCustomerRate($tenant, $startDate)
        ];

        return Inertia::render('tenant/analytics/Bookings', [
            'tenant' => $tenant,
            'period' => $period,
            'data' => $bookingData
        ]);
    }

    public function customers(Request $request)
    {
        $tenant = app('tenant');
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $customerData = [
            'customer_acquisition' => $this->getCustomerAcquisition($tenant, $startDate),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue($tenant),
            'top_customers' => $this->getTopCustomers($tenant, $startDate),
            'customer_segments' => $this->getCustomerSegments($tenant, $startDate),
            'customer_satisfaction' => $this->getCustomerSatisfaction($tenant, $startDate),
            'churn_analysis' => $this->getChurnAnalysis($tenant, $startDate)
        ];

        return Inertia::render('tenant/analytics/Customers', [
            'tenant' => $tenant,
            'period' => $period,
            'data' => $customerData
        ]);
    }

    public function export(Request $request)
    {
        $tenant = app('tenant');
        $type = $request->get('type', 'overview');
        $period = $request->get('period', '30d');
        $format = $request->get('format', 'csv');

        $data = match($type) {
            'revenue' => $this->getRevenueExportData($tenant, $period),
            'billboards' => $this->getBillboardExportData($tenant, $period),
            'bookings' => $this->getBookingExportData($tenant, $period),
            'customers' => $this->getCustomerExportData($tenant, $period),
            default => $this->getOverviewExportData($tenant, $period)
        };

        $filename = "{$type}_analytics_{$period}_" . now()->format('Y-m-d') . ".{$format}";

        if ($format === 'csv') {
            return $this->exportToCsv($data, $filename);
        } else {
            return $this->exportToExcel($data, $filename);
        }
    }

    private function getOverviewMetrics($tenant, $startDate): array
    {
        return [
            'total_revenue' => $this->getTotalRevenue($tenant, $startDate),
            'total_bookings' => $this->getTotalBookings($tenant, $startDate),
            'average_booking_value' => $this->getAverageBookingValue($tenant, $startDate),
            'utilization_rate' => $this->getOverallUtilizationRate($tenant, $startDate),
            'conversion_rate' => $this->getConversionRate($tenant, $startDate),
            'revenue_growth' => $this->getRevenueGrowthPercentage($tenant, $startDate)
        ];
    }

    private function getRevenueAnalytics($tenant, $startDate): array
    {
        return [
            'daily_revenue' => $this->getDailyRevenue($tenant, $startDate),
            'monthly_comparison' => $this->getMonthlyComparison($tenant),
            'revenue_by_category' => $this->getRevenueBySizeCategory($tenant, $startDate)
        ];
    }

    private function getUtilizationMetrics($tenant, $startDate): array
    {
        return [
            'overall_utilization' => $this->getOverallUtilizationRate($tenant, $startDate),
            'utilization_by_billboard' => $this->getBillboardUtilizationRates($tenant, $startDate),
            'peak_utilization_times' => $this->getPeakUtilizationTimes($tenant, $startDate)
        ];
    }

    private function getPerformanceMetrics($tenant, $startDate): array
    {
        return [
            'top_performing_billboards' => $this->getTopPerformingBillboards($tenant, $startDate),
            'underperforming_billboards' => $this->getUnderperformingBillboards($tenant, $startDate),
            'location_performance' => $this->getLocationPerformance($tenant, $startDate)
        ];
    }

    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '6m' => now()->subMonths(6),
            '1y' => now()->subYear(),
            default => now()->subDays(30)
        };
    }

    private function getTotalRevenue($tenant, $startDate): float
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
        ->where('created_at', '>=', $startDate)
        ->whereIn('status', ['confirmed', 'completed'])
        ->sum('final_price') ?? 0;
    }

    private function getTotalBookings($tenant, $startDate): int
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->where('created_at', '>=', $startDate)->count();
    }

    private function getAverageBookingValue($tenant, $startDate): float
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
        ->where('created_at', '>=', $startDate)
        ->whereIn('status', ['confirmed', 'completed'])
        ->avg('final_price') ?? 0;
    }

    private function getOverallUtilizationRate($tenant, $startDate): float
    {
        $totalBillboards = Billboard::where('tenant_id', $tenant->id)->count();
        $totalDays = now()->diffInDays($startDate) ?: 1;
        $totalAvailableDays = $totalBillboards * $totalDays;

        $bookedDays = DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->sum(DB::raw('DATEDIFF(LEAST(bookings.end_date, NOW()), GREATEST(bookings.start_date, ?))'), [$startDate]);

        return $totalAvailableDays > 0 ? round(($bookedDays / $totalAvailableDays) * 100, 1) : 0;
    }

    private function getConversionRate($tenant, $startDate): float
    {
        $totalInquiries = Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->where('created_at', '>=', $startDate)->count();

        $confirmedBookings = Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
        ->where('created_at', '>=', $startDate)
        ->whereIn('status', ['confirmed', 'completed'])
        ->count();

        return $totalInquiries > 0 ? round(($confirmedBookings / $totalInquiries) * 100, 1) : 0;
    }

    private function getDailyRevenue($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('DATE(bookings.created_at) as date, SUM(bookings.final_price) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue
                ];
            })
            ->toArray();
    }

    private function getMonthlyRevenue($tenant): array
    {
        return DB::table('bookings')
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
                    'revenue' => (float) $item->revenue
                ];
            })
            ->toArray();
    }

    private function getRevenueByBillboard($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('billboards.title, billboards.location, SUM(bookings.final_price) as revenue')
            ->groupBy('billboards.id', 'billboards.title', 'billboards.location')
            ->orderByDesc('revenue')
            ->take(10)
            ->get()
            ->toArray();
    }

    private function getRevenueBySizeCategory($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('billboards.size, SUM(bookings.final_price) as revenue, COUNT(bookings.id) as bookings')
            ->groupBy('billboards.size')
            ->get()
            ->toArray();
    }

    private function getBillboardUtilizationRates($tenant, $startDate): array
    {
        $billboards = Billboard::where('tenant_id', $tenant->id)->get();
        $utilization = [];

        foreach ($billboards as $billboard) {
            $totalDays = now()->diffInDays($startDate) ?: 1;
            $bookedDays = $billboard->bookings()
                ->where('created_at', '>=', $startDate)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum(DB::raw('DATEDIFF(end_date, start_date)'));

            $utilization[] = [
                'billboard_id' => $billboard->id,
                'title' => $billboard->title,
                'location' => $billboard->location,
                'utilization_rate' => $totalDays > 0 ? round(($bookedDays / $totalDays) * 100, 1) : 0
            ];
        }

        return $utilization;
    }

    private function getTopPerformingBillboards($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('
                billboards.id,
                billboards.title,
                billboards.location,
                SUM(bookings.final_price) as total_revenue,
                COUNT(bookings.id) as total_bookings,
                AVG(bookings.final_price) as avg_booking_value
            ')
            ->groupBy('billboards.id', 'billboards.title', 'billboards.location')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get()
            ->toArray();
    }

    private function exportToCsv($data, $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // Additional helper methods for other analytics data...
    private function getRevenueGrowthPercentage($tenant, $startDate): float
    {
        $currentPeriodRevenue = $this->getTotalRevenue($tenant, $startDate);
        $previousPeriodStart = $startDate->copy()->sub(now()->diffAsCarbonInterval($startDate));
        $previousPeriodRevenue = $this->getTotalRevenue($tenant, $previousPeriodStart);

        if ($previousPeriodRevenue > 0) {
            return round((($currentPeriodRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100, 1);
        }

        return 0;
    }

    private function getMonthlyComparison($tenant): array
    {
        $thisMonth = $this->getTotalRevenue($tenant, now()->startOfMonth());
        $lastMonth = $this->getTotalRevenue($tenant, now()->subMonth()->startOfMonth());

        return [
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'growth_percentage' => $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0
        ];
    }

    private function getPeakUtilizationTimes($tenant, $startDate): array
    {
        // This would return peak booking times/seasons
        return [];
    }

    private function getUnderperformingBillboards($tenant, $startDate): array
    {
        return DB::table('billboards')
            ->leftJoin('bookings', function ($join) use ($startDate) {
                $join->on('billboards.id', '=', 'bookings.billboard_id')
                     ->where('bookings.created_at', '>=', $startDate)
                     ->whereIn('bookings.status', ['confirmed', 'completed']);
            })
            ->where('billboards.tenant_id', $tenant->id)
            ->selectRaw('
                billboards.id,
                billboards.title,
                billboards.location,
                COALESCE(SUM(bookings.final_price), 0) as total_revenue,
                COALESCE(COUNT(bookings.id), 0) as total_bookings
            ')
            ->groupBy('billboards.id', 'billboards.title', 'billboards.location')
            ->orderBy('total_revenue')
            ->take(5)
            ->get()
            ->toArray();
    }

    private function getLocationPerformance($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->selectRaw('billboards.location, SUM(bookings.final_price) as revenue, COUNT(bookings.id) as bookings')
            ->groupBy('billboards.location')
            ->orderByDesc('revenue')
            ->get()
            ->toArray();
    }

    // Placeholder methods for additional analytics features
    private function getBillboardSizeDistribution($tenant): array { return []; }
    private function getOccupancyCalendar($tenant): array { return []; }
    private function getAvailabilityTrends($tenant, $startDate): array { return []; }
    private function getBookingTrends($tenant, $startDate): array { return []; }
    private function getConversionFunnel($tenant, $startDate): array { return []; }
    private function getBookingSources($tenant, $startDate): array { return []; }
    private function getCancellationAnalysis($tenant, $startDate): array { return []; }
    private function getPeakBookingTimes($tenant, $startDate): array { return []; }
    private function getBookingDurationAnalysis($tenant, $startDate): array { return []; }
    private function getRepeatCustomerRate($tenant, $startDate): float { return 0; }
    private function getCustomerAcquisition($tenant, $startDate): array { return []; }
    private function getCustomerLifetimeValue($tenant): array { return []; }
    private function getTopCustomers($tenant, $startDate): array { return []; }
    private function getCustomerSegments($tenant, $startDate): array { return []; }
    private function getCustomerSatisfaction($tenant, $startDate): array { return []; }
    private function getChurnAnalysis($tenant, $startDate): array { return []; }
    private function getRevenueForecast($tenant): array { return []; }

    // Export data methods
    private function getRevenueExportData($tenant, $period): array { return []; }
    private function getBillboardExportData($tenant, $period): array { return []; }
    private function getBookingExportData($tenant, $period): array { return []; }
    private function getCustomerExportData($tenant, $period): array { return []; }
    private function getOverviewExportData($tenant, $period): array { return []; }
    private function exportToExcel($data, $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exportToCsv($data, $filename); // Fallback to CSV for now
    }
}
