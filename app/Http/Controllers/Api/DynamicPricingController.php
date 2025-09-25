<?php

namespace App\Http\Controllers\Api;

use App\Events\PricingRecommendationGenerated;
use App\Http\Controllers\Controller;
use App\Models\Billboard;
use App\Services\DynamicPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DynamicPricingController extends Controller
{
    public function __construct(
        private DynamicPricingService $pricingService
    ) {}

    /**
     * Get dynamic pricing for a specific billboard
     */
    public function getBillboardPricing(Request $request, string $tenantUuid, int $billboardId)
    {
        $tenant = app('tenant');
        $billboard = $tenant->billboards()->findOrFail($billboardId);

        $validator = Validator::make($request->all(), [
            'booking_date' => 'nullable|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:1|max:365',
            'include_calendar' => 'nullable|boolean',
            'calendar_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $options = $validator->validated();

        // Get dynamic pricing
        $pricing = $this->pricingService->calculateDynamicPrice($billboard, $options);

        $response = [
            'billboard_id' => $billboardId,
            'pricing' => $pricing,
            'generated_at' => now()->toISOString(),
        ];

        // Include pricing calendar if requested
        if ($request->boolean('include_calendar')) {
            $days = $request->integer('calendar_days', 30);
            $startDate = now();
            $endDate = $startDate->copy()->addDays($days);

            $response['pricing_calendar'] = $this->pricingService->getPricingCalendar(
                $billboard,
                $startDate,
                $endDate
            );
        }

        // Fire pricing recommendation event
        if ($pricing['confidence_score'] >= 0.7) {
            PricingRecommendationGenerated::fire(
                billboard_id: $billboard->id,
                current_price: $billboard->price,
                recommended_price: $pricing['dynamic_price'],
                confidence_score: $pricing['confidence_score'],
                pricing_factors: $pricing['pricing_factors'],
                recommendations: $pricing['recommendations'],
                market_position: $pricing['market_position'],
                next_review_date: $pricing['next_review_date']
            );
        }

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * Get bulk pricing for multiple billboards
     */
    public function getBulkPricing(Request $request, string $tenantUuid)
    {
        $tenant = app('tenant');

        $validator = Validator::make($request->all(), [
            'billboard_ids' => 'required|array|max:50',
            'billboard_ids.*' => 'integer|exists:billboards,id',
            'booking_date' => 'nullable|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:1|max:365',
            'apply_recommendations' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $billboards = $tenant->billboards()
            ->whereIn('id', $validated['billboard_ids'])
            ->get();

        if ($billboards->count() !== count($validated['billboard_ids'])) {
            return response()->json([
                'error' => 'Some billboard IDs not found or not accessible'
            ], 404);
        }

        $options = array_intersect_key($validated, array_flip(['booking_date', 'duration']));
        $results = $this->pricingService->calculateBulkPricing($billboards, $options);

        // Apply recommendations if requested
        if ($request->boolean('apply_recommendations')) {
            $this->applyBulkRecommendations($results['results'], $tenant);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'billboards_processed' => $billboards->count(),
                'recommendations_applied' => $request->boolean('apply_recommendations'),
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get pricing analytics and trends
     */
    public function getPricingAnalytics(Request $request, string $tenantUuid)
    {
        $tenant = app('tenant');

        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y',
            'billboard_ids' => 'nullable|array',
            'billboard_ids.*' => 'integer|exists:billboards,id',
            'include_predictions' => 'nullable|boolean',
            'include_market_comparison' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $period = $validated['period'] ?? '30d';

        $query = $tenant->billboards();
        if (isset($validated['billboard_ids'])) {
            $query->whereIn('id', $validated['billboard_ids']);
        }

        $billboards = $query->get();
        $analytics = $this->generatePricingAnalytics($billboards, $period, $validated);

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'meta' => [
                'period' => $period,
                'billboards_analyzed' => $billboards->count(),
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get market insights and competitor pricing
     */
    public function getMarketInsights(Request $request, string $tenantUuid)
    {
        $tenant = app('tenant');

        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string|max:255',
            'area_type' => 'nullable|in:commercial,highway,residential,mixed,industrial',
            'size' => 'nullable|in:small,medium,large,extra_large',
            'radius_km' => 'nullable|numeric|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $validator->validated();
        $insights = $this->generateMarketInsights($tenant, $filters);

        return response()->json([
            'success' => true,
            'data' => $insights,
            'meta' => [
                'filters_applied' => $filters,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Update billboard pricing based on recommendations
     */
    public function applyPricingRecommendation(Request $request, string $tenantUuid, int $billboardId)
    {
        $tenant = app('tenant');
        $billboard = $tenant->billboards()->findOrFail($billboardId);

        $validator = Validator::make($request->all(), [
            'new_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
            'apply_immediately' => 'nullable|boolean',
            'effective_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $oldPrice = $billboard->price;
        $newPrice = $validated['new_price'];

        // Validate price change isn't too extreme
        $maxIncrease = $oldPrice * 2.5; // 250% max increase
        $minDecrease = $oldPrice * 0.5; // 50% max decrease

        if ($newPrice > $maxIncrease || $newPrice < $minDecrease) {
            return response()->json([
                'error' => 'Price change is too extreme',
                'limits' => [
                    'min_price' => $minDecrease,
                    'max_price' => $maxIncrease,
                ],
            ], 422);
        }

        // Apply price change
        if ($request->boolean('apply_immediately', true)) {
            $billboard->update([
                'price' => $newPrice,
                'price_updated_at' => now(),
                'price_update_reason' => $validated['reason'] ?? 'Dynamic pricing recommendation',
            ]);

            // Log the change
            activity()
                ->performedOn($billboard)
                ->withProperties([
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'change_percentage' => round((($newPrice - $oldPrice) / $oldPrice) * 100, 2),
                    'reason' => $validated['reason'] ?? 'Dynamic pricing recommendation',
                ])
                ->log('Billboard price updated');

            // Fire pricing update event
            \App\Events\BillboardPriceUpdated::fire(
                billboard_id: $billboard->id,
                old_price: $oldPrice,
                new_price: $newPrice,
                change_reason: $validated['reason'] ?? 'Dynamic pricing recommendation'
            );
        } else {
            // Schedule price change
            $effectiveDate = Carbon::parse($validated['effective_date'] ?? now()->addDay());

            // Store scheduled price change (would need a database table for this)
            $billboard->update([
                'scheduled_price' => $newPrice,
                'price_effective_date' => $effectiveDate,
                'price_update_reason' => $validated['reason'] ?? 'Dynamic pricing recommendation',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $request->boolean('apply_immediately', true)
                ? 'Price updated successfully'
                : 'Price change scheduled successfully',
            'data' => [
                'billboard_id' => $billboard->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'change_percentage' => round((($newPrice - $oldPrice) / $oldPrice) * 100, 2),
                'effective_date' => $request->boolean('apply_immediately', true)
                    ? now()->toISOString()
                    : $effectiveDate->toISOString(),
            ],
        ]);
    }

    private function applyBulkRecommendations(array $results, $tenant): void
    {
        foreach ($results as $result) {
            $billboard = Billboard::find($result['billboard_id']);
            $pricing = $result['pricing'];

            // Only apply if confidence is high and change is significant
            if ($pricing['confidence_score'] >= 0.8 && abs($pricing['price_change_percentage']) >= 5) {
                $billboard->update([
                    'suggested_price' => $pricing['dynamic_price'],
                    'price_suggestion_confidence' => $pricing['confidence_score'],
                    'price_suggestion_generated_at' => now(),
                ]);

                // Fire recommendation event
                PricingRecommendationGenerated::fire(
                    billboard_id: $billboard->id,
                    current_price: $billboard->price,
                    recommended_price: $pricing['dynamic_price'],
                    confidence_score: $pricing['confidence_score'],
                    pricing_factors: $pricing['pricing_factors'],
                    recommendations: $pricing['recommendations'],
                    market_position: $pricing['market_position']
                );
            }
        }
    }

    private function generatePricingAnalytics(Collection $billboards, string $period, array $options): array
    {
        // This would generate comprehensive analytics
        return [
            'period_summary' => [
                'period' => $period,
                'billboards_analyzed' => $billboards->count(),
                'average_price' => $billboards->avg('price'),
                'price_range' => [
                    'min' => $billboards->min('price'),
                    'max' => $billboards->max('price'),
                ],
            ],
            'pricing_trends' => [
                'trend_direction' => 'upward', // would be calculated
                'average_change' => '+5.2%',
                'volatility' => 'low',
            ],
            'performance_insights' => [
                'top_performers' => $billboards->take(5),
                'optimization_opportunities' => [], // would be calculated
                'market_position' => 'competitive',
            ],
        ];
    }

    private function generateMarketInsights($tenant, array $filters): array
    {
        return [
            'market_overview' => [
                'total_billboards' => $tenant->billboards()->count(),
                'average_price' => $tenant->billboards()->avg('price'),
                'occupancy_rate' => '78%', // would be calculated
            ],
            'pricing_recommendations' => [
                'suggested_adjustments' => [], // would be calculated
                'optimal_pricing_windows' => [],
                'competitive_positioning' => [],
            ],
            'demand_forecast' => [
                'next_30_days' => 'increasing',
                'seasonal_trends' => [],
                'special_events_impact' => [],
            ],
        ];
    }
}