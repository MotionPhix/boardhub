<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Billboard;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BillboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $billboards = Billboard::where('tenant_id', $tenant->id)
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('availability_status', $status);
            })
            ->when($request->size, function ($query, $size) {
                return $query->where('size', $size);
            })
            ->withCount(['bookings' => function ($query) {
                $query->whereIn('status', ['confirmed', 'active']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Billboard::where('tenant_id', $tenant->id)->count(),
            'available' => Billboard::where('tenant_id', $tenant->id)->where('availability_status', 'available')->count(),
            'booked' => Billboard::where('tenant_id', $tenant->id)->where('availability_status', 'booked')->count(),
            'maintenance' => Billboard::where('tenant_id', $tenant->id)->where('availability_status', 'unavailable')->count(),
        ];

        $filters = [
            'sizes' => Billboard::where('tenant_id', $tenant->id)->distinct()->pluck('size')->filter(),
            'statuses' => [
                'available' => 'Available',
                'booked' => 'Booked',
                'unavailable' => 'Unavailable'
            ]
        ];

        return Inertia::render('tenant/billboards/Index', [
            'tenant' => $tenant,
            'billboards' => $billboards,
            'stats' => $stats,
            'filters' => $filters,
            'queryParams' => $request->query()
        ]);
    }

    public function show(Billboard $billboard)
    {
        $tenant = app('tenant');

        // Ensure billboard belongs to tenant
        if ($billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        $billboard->load(['bookings' => function ($query) {
            $query->with('client')->latest();
        }]);

        // Calculate performance metrics
        $totalRevenue = $billboard->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('final_price');

        $averageBookingValue = $billboard->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->avg('final_price');

        $utilizationRate = $this->calculateUtilizationRate($billboard);

        $upcomingBookings = $billboard->bookings()
            ->where('start_date', '>', now())
            ->where('status', 'confirmed')
            ->with('client')
            ->orderBy('start_date')
            ->take(5)
            ->get();

        return Inertia::render('tenant/billboards/Show', [
            'tenant' => $tenant,
            'billboard' => $billboard,
            'performance' => [
                'total_revenue' => $totalRevenue,
                'average_booking_value' => round($averageBookingValue, 2),
                'utilization_rate' => $utilizationRate,
                'total_bookings' => $billboard->bookings()->count(),
                'active_bookings' => $billboard->bookings()->where('status', 'active')->count(),
            ],
            'upcomingBookings' => $upcomingBookings
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');

        return Inertia::render('tenant/billboards/Create', [
            'tenant' => $tenant,
            'sizeOptions' => $this->getSizeOptions(),
            'locationSuggestions' => $this->getLocationSuggestions($tenant)
        ]);
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'size' => 'required|string',
            'price_per_day' => 'required|numeric|min:0',
            'visibility_rating' => 'required|integer|between:1,5',
            'traffic_rating' => 'required|integer|between:1,5',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after:available_from',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_url' => 'nullable|url',
            'impressions_per_day' => 'nullable|integer|min:0'
        ]);

        // Handle image uploads
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('billboards', 'public');
                $images[] = Storage::url($path);
            }
        }

        $billboard = Billboard::create([
            ...$validated,
            'tenant_id' => $tenant->id,
            'images' => $images,
            'availability_status' => 'available',
            'status' => 'active'
        ]);

        return redirect()
            ->route('tenant.manage.billboards.show', ['tenant' => $tenant->uuid, 'billboard' => $billboard->id])
            ->with('success', 'Billboard created successfully!');
    }

    public function edit(Billboard $billboard)
    {
        $tenant = app('tenant');

        if ($billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        return Inertia::render('tenant/billboards/Edit', [
            'tenant' => $tenant,
            'billboard' => $billboard,
            'sizeOptions' => $this->getSizeOptions(),
            'locationSuggestions' => $this->getLocationSuggestions($tenant)
        ]);
    }

    public function update(Request $request, Billboard $billboard)
    {
        $tenant = app('tenant');

        if ($billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'size' => 'required|string',
            'price_per_day' => 'required|numeric|min:0',
            'visibility_rating' => 'required|integer|between:1,5',
            'traffic_rating' => 'required|integer|between:1,5',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after:available_from',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'availability_status' => 'required|in:available,booked,unavailable',
            'image_url' => 'nullable|url',
            'impressions_per_day' => 'nullable|integer|min:0'
        ]);

        // Handle new image uploads
        $images = $billboard->images ?? [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $path = $image->store('billboards', 'public');
                $images[] = Storage::url($path);
            }
        }

        $billboard->update([
            ...$validated,
            'images' => $images
        ]);

        return redirect()
            ->route('tenant.manage.billboards.show', ['tenant' => $tenant->uuid, 'billboard' => $billboard->id])
            ->with('success', 'Billboard updated successfully!');
    }

    public function destroy(Billboard $billboard)
    {
        $tenant = app('tenant');

        if ($billboard->tenant_id !== $tenant->id) {
            abort(403);
        }

        // Check if billboard has active bookings
        $activeBookings = $billboard->bookings()->whereIn('status', ['confirmed', 'active'])->count();

        if ($activeBookings > 0) {
            return back()->withErrors(['error' => 'Cannot delete billboard with active bookings.']);
        }

        // Delete associated images
        if ($billboard->images) {
            foreach ($billboard->images as $imageUrl) {
                $imagePath = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
                Storage::disk('public')->delete($imagePath);
            }
        }

        $billboard->delete();

        return redirect()
            ->route('tenant.manage.billboards.index', ['tenant' => $tenant->uuid])
            ->with('success', 'Billboard deleted successfully!');
    }

    private function calculateUtilizationRate(Billboard $billboard): float
    {
        $totalDays = now()->diffInDays($billboard->created_at) ?: 1;

        $bookedDays = $billboard->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum(\DB::raw('DATEDIFF(end_date, start_date)'));

        return round(($bookedDays / $totalDays) * 100, 1);
    }

    private function getSizeOptions(): array
    {
        return [
            '6x3' => '6\' x 3\' (Small)',
            '12x6' => '12\' x 6\' (Medium)',
            '24x12' => '24\' x 12\' (Large)',
            '48x14' => '48\' x 14\' (Billboard)',
            '60x20' => '60\' x 20\' (Bulletin)',
            'custom' => 'Custom Size'
        ];
    }

    private function getLocationSuggestions(Tenant $tenant): array
    {
        return Billboard::where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('location')
            ->take(10)
            ->toArray();
    }
}
