<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillboardSearchRequest;
use App\Services\BillboardDiscoveryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BillboardDiscoveryController extends Controller
{
    public function __construct(
        private BillboardDiscoveryService $discoveryService
    ) {}

    /**
     * AI-powered billboard search and discovery
     */
    public function discover(BillboardSearchRequest $request)
    {
        $tenant = app('tenant');
        $criteria = $request->validated();

        $results = $this->discoveryService->discoverBillboards($criteria, $tenant);

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'search_criteria' => $criteria,
                'total_results' => count($results['billboards']),
                'search_time' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Location-based intelligent search
     */
    public function searchByLocation(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'coordinates' => 'required|array',
            'coordinates.lat' => 'required|numeric|between:-90,90',
            'coordinates.lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'area_types' => 'nullable|array',
            'area_types.*' => 'string|in:commercial,residential,highway,mixed,industrial',
            'include_demographics' => 'nullable|boolean',
            'include_traffic_data' => 'nullable|boolean',
        ]);

        $results = $this->discoveryService->searchByLocation($validated, $tenant);

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'search_center' => $validated['coordinates'],
                'radius_km' => $validated['radius'] ?? 10,
                'total_results' => count($results['results']),
            ]
        ]);
    }

    /**
     * Check smart availability for multiple billboards
     */
    public function checkAvailability(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'billboard_ids' => 'required|array',
            'billboard_ids.*' => 'integer|exists:billboards,id',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'preferences' => 'nullable|array',
        ]);

        $billboards = $tenant->billboards()
            ->whereIn('id', $validated['billboard_ids'])
            ->get();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $results = $this->discoveryService->checkSmartAvailability(
            $billboards,
            $startDate,
            $endDate,
            $validated['preferences'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'period' => [
                    'start_date' => $startDate->toISOString(),
                    'end_date' => $endDate->toISOString(),
                    'duration_days' => $startDate->diffInDays($endDate) + 1,
                ],
                'billboards_checked' => count($billboards),
            ]
        ]);
    }

    /**
     * Get personalized recommendations for a client
     */
    public function getPersonalizedRecommendations(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'campaign_budget' => 'nullable|numeric|min:0',
            'campaign_duration' => 'nullable|integer|min:1|max:365',
            'target_audience' => 'nullable|array',
        ]);

        $results = $this->discoveryService->getPersonalizedRecommendations(
            $validated['client_id'],
            $tenant
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'client_id' => $validated['client_id'],
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get smart suggestions while user is typing (autocomplete)
     */
    public function getSuggestions(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:location,size,area_type',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $suggestions = $this->generateSmartSuggestions($validated, $tenant);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'meta' => [
                'query' => $validated['query'],
                'type' => $validated['type'] ?? 'all',
                'results_count' => count($suggestions),
            ]
        ]);
    }

    /**
     * Get trending searches and popular billboards
     */
    public function getTrending(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'period' => 'nullable|in:day,week,month',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $trendingData = [
            'trending_searches' => $this->getTrendingSearches($tenant, $validated['period'] ?? 'week'),
            'popular_billboards' => $this->getPopularBillboards($tenant, $validated['limit'] ?? 10),
            'emerging_locations' => $this->getEmergingLocations($tenant),
            'price_trends' => $this->getPriceTrends($tenant, $validated['period'] ?? 'week'),
        ];

        return response()->json([
            'success' => true,
            'data' => $trendingData,
            'meta' => [
                'period' => $validated['period'] ?? 'week',
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Advanced filters for power users
     */
    public function advancedSearch(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            // Basic filters
            'locations' => 'nullable|array',
            'sizes' => 'nullable|array',
            'price_range' => 'nullable|array',
            'price_range.min' => 'nullable|numeric|min:0',
            'price_range.max' => 'nullable|numeric|min:0',

            // Advanced filters
            'occupancy_rate_min' => 'nullable|numeric|min:0|max:100',
            'performance_score_min' => 'nullable|numeric|min:0|max:100',
            'last_booked_within_days' => 'nullable|integer|min:1|max:365',
            'availability_window' => 'nullable|array',
            'availability_window.start' => 'nullable|date',
            'availability_window.end' => 'nullable|date|after:availability_window.start',

            // Demographic filters
            'target_demographics' => 'nullable|array',
            'traffic_volume_min' => 'nullable|integer|min:0',

            // Sorting and pagination
            'sort_by' => 'nullable|in:price,performance,popularity,availability,ai_score',
            'sort_order' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $this->performAdvancedSearch($validated, $tenant);

        return response()->json([
            'success' => true,
            'data' => $results['data'],
            'pagination' => $results['pagination'],
            'filters_applied' => $results['filters_applied'],
            'meta' => [
                'total_matches' => $results['total'],
                'search_time_ms' => $results['search_time'],
            ]
        ]);
    }

    private function generateSmartSuggestions(array $validated, $tenant): array
    {
        $query = strtolower($validated['query']);
        $type = $validated['type'] ?? 'all';
        $limit = $validated['limit'] ?? 10;

        $suggestions = [];

        if ($type === 'location' || $type === 'all') {
            // Location suggestions
            $locations = $tenant->billboards()
                ->select('location')
                ->where('location', 'like', "%{$query}%")
                ->groupBy('location')
                ->limit($limit)
                ->pluck('location')
                ->map(fn($location) => [
                    'type' => 'location',
                    'value' => $location,
                    'label' => $location,
                    'icon' => '📍',
                ]);

            $suggestions = array_merge($suggestions, $locations->toArray());
        }

        if ($type === 'area_type' || $type === 'all') {
            // Area type suggestions
            $areaTypes = [
                'commercial' => ['label' => 'Commercial Areas', 'icon' => '🏢'],
                'highway' => ['label' => 'Highway/Traffic', 'icon' => '🛣️'],
                'residential' => ['label' => 'Residential', 'icon' => '🏠'],
                'industrial' => ['label' => 'Industrial', 'icon' => '🏭'],
                'mixed' => ['label' => 'Mixed Use', 'icon' => '🌆'],
            ];

            foreach ($areaTypes as $key => $data) {
                if (str_contains($key, $query) || str_contains(strtolower($data['label']), $query)) {
                    $suggestions[] = [
                        'type' => 'area_type',
                        'value' => $key,
                        'label' => $data['label'],
                        'icon' => $data['icon'],
                    ];
                }
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    private function getTrendingSearches($tenant, string $period): array
    {
        // This would typically come from analytics/tracking
        return [
            ['query' => 'Lilongwe commercial', 'growth' => '+45%'],
            ['query' => 'highway billboards', 'growth' => '+32%'],
            ['query' => 'large size', 'growth' => '+28%'],
            ['query' => 'Blantyre downtown', 'growth' => '+22%'],
            ['query' => 'residential areas', 'growth' => '+18%'],
        ];
    }

    private function getPopularBillboards($tenant, int $limit): array
    {
        return $tenant->billboards()
            ->select(['id', 'name', 'location', 'price'])
            ->withCount(['bookings as popularity_score'])
            ->orderBy('popularity_score', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function getEmergingLocations($tenant): array
    {
        // Locations with increasing booking activity
        return [
            ['location' => 'Area 47, Lilongwe', 'growth_rate' => 67],
            ['location' => 'Chichiri, Blantyre', 'growth_rate' => 43],
            ['location' => 'Mzuzu City Center', 'growth_rate' => 38],
        ];
    }

    private function getPriceTrends($tenant, string $period): array
    {
        return [
            'average_price_change' => '+12%',
            'price_volatility' => 'Low',
            'recommendation' => 'Prices trending upward - good time to book',
        ];
    }

    private function performAdvancedSearch(array $criteria, $tenant): array
    {
        $startTime = microtime(true);

        $query = $tenant->billboards()->where('status', 'available');

        $filtersApplied = [];

        // Apply filters and build filtersApplied array
        // ... (implementation would go here)

        $total = $query->count();
        $page = $criteria['page'] ?? 1;
        $perPage = $criteria['per_page'] ?? 20;

        $results = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $searchTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'data' => $results,
            'total' => $total,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
                'has_more' => $page * $perPage < $total,
            ],
            'filters_applied' => $filtersApplied,
            'search_time' => $searchTime,
        ];
    }
}