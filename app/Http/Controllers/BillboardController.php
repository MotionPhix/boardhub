<?php

namespace App\Http\Controllers;

use App\Models\Billboard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Billboard::query();

        // Apply search filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            // Default to available billboards
            $query->where('status', 'available');
        }

        // Filter by location/city
        if ($request->location) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Price range filtering
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        $billboards = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Get filter options for the frontend
        $locations = Billboard::distinct()
            ->pluck('location')
            ->map(fn($location) => explode(',', $location)[0])
            ->unique()
            ->sort()
            ->values();

        $priceRange = [
            'min' => Billboard::min('price'),
            'max' => Billboard::max('price'),
        ];

        return Inertia::render('billboards/Index', [
            'billboards' => $billboards,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'location' => $request->location,
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
            ],
            'locations' => $locations,
            'priceRange' => $priceRange,
        ]);
    }

    public function show(Billboard $billboard)
    {
        return Inertia::render('billboards/Show', [
            'billboard' => $billboard,
            'availabilityCalendar' => $this->getAvailabilityCalendar($billboard),
            'suggestedDates' => [
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->addWeeks(5)->format('Y-m-d'),
            ],
        ]);
    }

    private function getAvailabilityCalendar(Billboard $billboard): array
    {
        // TODO: Integrate with BillboardState to show real availability
        // For now, return simple availability data
        return [
            'available_dates' => [],
            'booked_dates' => [],
            'maintenance_dates' => [],
        ];
    }
}
