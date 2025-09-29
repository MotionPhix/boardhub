<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Billboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $bookings = Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->with(['billboard', 'client'])
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('billboard', function ($billboardQuery) use ($search) {
                        $billboardQuery->where('title', 'like', "%{$search}%")
                                     ->orWhere('location', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->billboard_id, function ($query, $billboardId) {
                return $query->where('billboard_id', $billboardId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                return $query->where('start_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                return $query->where('end_date', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->count(),
            'pending' => Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->where('status', 'pending')->count(),
            'confirmed' => Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->where('status', 'confirmed')->count(),
            'active' => Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->where('status', 'active')->count(),
            'completed' => Booking::whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->where('status', 'completed')->count(),
        ];

        $filters = [
            'billboards' => Billboard::where('tenant_id', $tenant->id)
                ->select('id', 'title', 'location')
                ->get(),
            'statuses' => [
                'pending' => 'Pending Review',
                'confirmed' => 'Confirmed',
                'active' => 'Active',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ]
        ];

        return Inertia::render('tenant/bookings/Index', [
            'tenant' => $tenant,
            'bookings' => $bookings,
            'stats' => $stats,
            'filters' => $filters,
            'queryParams' => $request->query()
        ]);
    }

    public function show(Booking $booking)
    {
        $tenant = app('tenant');

        // Ensure booking belongs to tenant's billboard
        if ($booking->billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        $booking->load(['billboard', 'client', 'payments']);

        // Calculate additional metrics
        $duration = $booking->start_date->diffInDays($booking->end_date) + 1;
        $dailyRate = $booking->final_price ? $booking->final_price / $duration : 0;

        // Get related bookings for the same billboard
        $relatedBookings = Booking::where('billboard_id', $booking->billboard_id)
            ->where('id', '!=', $booking->id)
            ->with('client')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('tenant/bookings/Show', [
            'tenant' => $tenant,
            'booking' => $booking,
            'metrics' => [
                'duration_days' => $duration,
                'daily_rate' => round($dailyRate, 2),
                'total_revenue' => $booking->final_price ?? $booking->requested_price,
                'payment_status' => $this->getPaymentStatus($booking)
            ],
            'relatedBookings' => $relatedBookings
        ]);
    }

    public function approve(Request $request, Booking $booking)
    {
        $tenant = app('tenant');

        if ($booking->billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending bookings can be approved.']);
        }

        $validated = $request->validate([
            'final_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $booking->update([
            'status' => 'confirmed',
            'final_price' => $validated['final_price'],
            'approved_at' => now(),
            'notes' => $validated['notes']
        ]);

        return back()->with('success', 'Booking approved successfully!');
    }

    public function reject(Request $request, Booking $booking)
    {
        $tenant = app('tenant');

        if ($booking->billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending bookings can be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'rejected_at' => now()
        ]);

        return back()->with('success', 'Booking rejected.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        $tenant = app('tenant');

        if ($booking->billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['confirmed', 'active'])) {
            return back()->withErrors(['error' => 'Only confirmed or active bookings can be cancelled.']);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string',
            'refund_amount' => 'nullable|numeric|min:0'
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_at' => now(),
            'refund_amount' => $validated['refund_amount'] ?? 0
        ]);

        return back()->with('success', 'Booking cancelled successfully.');
    }

    public function bulkAction(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id',
            'action' => 'required|in:approve,reject,cancel',
            'final_price' => 'required_if:action,approve|numeric|min:0',
            'rejection_reason' => 'required_if:action,reject|string',
            'cancellation_reason' => 'required_if:action,cancel|string'
        ]);

        $bookings = Booking::whereIn('id', $validated['booking_ids'])
            ->whereHas('billboard', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->get();

        $processed = 0;

        foreach ($bookings as $booking) {
            switch ($validated['action']) {
                case 'approve':
                    if ($booking->status === 'pending') {
                        $booking->update([
                            'status' => 'confirmed',
                            'final_price' => $validated['final_price'],
                            'approved_at' => now()
                        ]);
                        $processed++;
                    }
                    break;

                case 'reject':
                    if ($booking->status === 'pending') {
                        $booking->update([
                            'status' => 'rejected',
                            'rejection_reason' => $validated['rejection_reason'],
                            'rejected_at' => now()
                        ]);
                        $processed++;
                    }
                    break;

                case 'cancel':
                    if (in_array($booking->status, ['confirmed', 'active'])) {
                        $booking->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => $validated['cancellation_reason'],
                            'cancelled_at' => now()
                        ]);
                        $processed++;
                    }
                    break;
            }
        }

        return back()->with('success', "Successfully processed {$processed} bookings.");
    }

    public function analytics(Request $request)
    {
        $tenant = app('tenant');

        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateForPeriod($period);

        $bookingStats = [
            'total_bookings' => $this->getBookingCount($tenant, $startDate),
            'total_revenue' => $this->getTotalRevenue($tenant, $startDate),
            'average_booking_value' => $this->getAverageBookingValue($tenant, $startDate),
            'conversion_rate' => $this->getConversionRate($tenant, $startDate)
        ];

        $revenueByMonth = $this->getRevenueByMonth($tenant, $startDate);
        $bookingsByStatus = $this->getBookingsByStatus($tenant, $startDate);
        $topBillboards = $this->getTopPerformingBillboards($tenant, $startDate);

        return Inertia::render('tenant/bookings/Analytics', [
            'tenant' => $tenant,
            'period' => $period,
            'stats' => $bookingStats,
            'charts' => [
                'revenue_by_month' => $revenueByMonth,
                'bookings_by_status' => $bookingsByStatus,
                'top_billboards' => $topBillboards
            ]
        ]);
    }

    private function getPaymentStatus(Booking $booking): string
    {
        if (!$booking->payments || $booking->payments->isEmpty()) {
            return 'unpaid';
        }

        $totalPaid = $booking->payments->where('status', 'completed')->sum('amount');
        $totalDue = $booking->final_price ?? $booking->requested_price;

        if ($totalPaid >= $totalDue) {
            return 'paid';
        } elseif ($totalPaid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30)
        };
    }

    private function getBookingCount($tenant, $startDate): int
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->where('created_at', '>=', $startDate)->count();
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

    private function getAverageBookingValue($tenant, $startDate): float
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
        ->where('created_at', '>=', $startDate)
        ->whereIn('status', ['confirmed', 'completed'])
        ->avg('final_price') ?? 0;
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

    private function getRevenueByMonth($tenant, $startDate): array
    {
        return DB::table('bookings')
            ->join('billboards', 'bookings.billboard_id', '=', 'billboards.id')
            ->where('billboards.tenant_id', $tenant->id)
            ->where('bookings.created_at', '>=', $startDate)
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

    private function getBookingsByStatus($tenant, $startDate): array
    {
        return Booking::whereHas('billboard', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
        ->where('created_at', '>=', $startDate)
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
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
                COUNT(bookings.id) as total_bookings
            ')
            ->groupBy('billboards.id', 'billboards.title', 'billboards.location')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get()
            ->toArray();
    }
}
