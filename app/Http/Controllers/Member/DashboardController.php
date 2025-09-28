<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Billboard;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $request->tenant;

        // Member dashboard - user's bookings and available billboards
        $stats = [
            'my_bookings' => Booking::where('user_id', $user->id)->where('tenant_id', $tenant->id)->count(),
            'active_bookings' => Booking::where('user_id', $user->id)->where('tenant_id', $tenant->id)->where('status', 'active')->count(),
            'total_spent' => Booking::where('user_id', $user->id)->where('tenant_id', $tenant->id)->where('status', 'completed')->sum('amount'),
            'available_billboards' => Billboard::where('tenant_id', $tenant->id)->where('status', 'available')->count(),
        ];

        $my_bookings = Booking::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->with('billboard')
            ->latest()
            ->take(5)
            ->get();

        $available_billboards = Billboard::where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('member/Dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'my_bookings' => $my_bookings,
            'available_billboards' => $available_billboards,
        ]);
    }
}