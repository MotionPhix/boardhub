<?php

namespace App\Http\Controllers;

use App\Models\Billboard;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        // Get featured billboards (available ones with good locations)
        $featuredBillboards = Billboard::where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($billboard) {
                return [
                    'id' => $billboard->id,
                    'name' => $billboard->name,
                    'location' => $billboard->location,
                    'size' => $billboard->size,
                    'price' => $billboard->price,
                    'status' => $billboard->status,
                    'description' => $billboard->description,
                    'image' => null, // TODO: Add media library integration
                ];
            });

        // Calculate platform stats
        $stats = [
            'totalBillboards' => Billboard::count(),
            'cities' => Billboard::distinct('location')
                ->pluck('location')
                ->map(fn($loc) => explode(',', $loc)[0])
                ->unique()
                ->count(),
            'activeAgencies' => Client::whereHas('contracts', function($q) {
                $q->where('agreement_status', 'active');
            })->count(),
        ];

        return Inertia::render('Home', [
            'featuredBillboards' => $featuredBillboards,
            'stats' => $stats,
        ]);
    }
}
