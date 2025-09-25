<?php

namespace App\Services;

use App\Models\Billboard;
use App\Models\Booking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DynamicPricingService
{
    private const CACHE_TTL = 3600; // 1 hour cache
    private const PRICE_CHANGE_THRESHOLD = 0.05; // 5% minimum change

    /**
     * Calculate dynamic price for a billboard based on AI algorithms
     */
    public function calculateDynamicPrice(Billboard $billboard, array $options = []): array
    {
        $cacheKey = "dynamic_price_" . $billboard->id . "_" . md5(serialize($options));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($billboard, $options) {
            $state = $billboard->getBillboardState();

            // Base price from billboard
            $basePrice = $billboard->price;

            // Calculate price multipliers
            $demandMultiplier = $this->calculateDemandMultiplier($billboard, $options);
            $seasonalMultiplier = $this->calculateSeasonalMultiplier($options);
            $locationMultiplier = $this->calculateLocationMultiplier($billboard);
            $performanceMultiplier = $this->calculatePerformanceMultiplier($state);
            $competitionMultiplier = $this->calculateCompetitionMultiplier($billboard);
            $urgencyMultiplier = $this->calculateUrgencyMultiplier($options);

            // AI-weighted pricing calculation
            $dynamicPrice = $basePrice *
                $demandMultiplier *
                $seasonalMultiplier *
                $locationMultiplier *
                $performanceMultiplier *
                $competitionMultiplier *
                $urgencyMultiplier;

            // Apply smart boundaries (prevent extreme pricing)
            $minPrice = $basePrice * 0.7; // Never go below 70% of base
            $maxPrice = $basePrice * 2.5; // Never go above 250% of base
            $dynamicPrice = max($minPrice, min($maxPrice, $dynamicPrice));

            // Calculate confidence score
            $confidence = $this->calculatePricingConfidence($billboard, [
                'demand' => $demandMultiplier,
                'seasonal' => $seasonalMultiplier,
                'location' => $locationMultiplier,
                'performance' => $performanceMultiplier,
                'competition' => $competitionMultiplier,
                'urgency' => $urgencyMultiplier,
            ]);

            return [
                'base_price' => $basePrice,
                'dynamic_price' => round($dynamicPrice, 2),
                'price_change_percentage' => round((($dynamicPrice - $basePrice) / $basePrice) * 100, 1),
                'pricing_factors' => [
                    'demand_multiplier' => $demandMultiplier,
                    'seasonal_multiplier' => $seasonalMultiplier,
                    'location_multiplier' => $locationMultiplier,
                    'performance_multiplier' => $performanceMultiplier,
                    'competition_multiplier' => $competitionMultiplier,
                    'urgency_multiplier' => $urgencyMultiplier,
                ],
                'confidence_score' => $confidence,
                'recommendations' => $this->generatePricingRecommendations($billboard, $dynamicPrice, $basePrice),
                'market_position' => $this->determineMarketPosition($billboard, $dynamicPrice),
                'next_review_date' => $this->calculateNextReviewDate($confidence),
            ];
        });
    }

    /**
     * Bulk calculate dynamic pricing for multiple billboards
     */
    public function calculateBulkPricing(Collection $billboards, array $options = []): array
    {
        $results = [];
        $marketData = $this->getMarketData($billboards->first()->tenant_id);

        foreach ($billboards as $billboard) {
            $pricing = $this->calculateDynamicPrice($billboard, array_merge($options, [
                'market_data' => $marketData,
            ]));

            $results[] = [
                'billboard_id' => $billboard->id,
                'billboard_name' => $billboard->name,
                'current_price' => $billboard->price,
                'pricing' => $pricing,
            ];
        }

        return [
            'results' => $results,
            'market_summary' => $this->generateMarketSummary($results),
            'pricing_trends' => $this->analyzePricingTrends($results),
        ];
    }

    /**
     * Get pricing recommendations for a specific date range
     */
    public function getPricingCalendar(Billboard $billboard, Carbon $startDate, Carbon $endDate): array
    {
        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $pricing = $this->calculateDynamicPrice($billboard, [
                'date' => $currentDate->toDateString(),
            ]);

            $calendar[] = [
                'date' => $currentDate->toDateString(),
                'day_of_week' => $currentDate->format('l'),
                'recommended_price' => $pricing['dynamic_price'],
                'price_change' => $pricing['price_change_percentage'],
                'demand_level' => $this->getDemandLevel($pricing['pricing_factors']['demand_multiplier']),
                'special_events' => $this->getSpecialEvents($currentDate),
            ];

            $currentDate->addDay();
        }

        return [
            'calendar' => $calendar,
            'period_summary' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'average_price' => round(collect($calendar)->avg('recommended_price'), 2),
                'price_volatility' => $this->calculatePriceVolatility($calendar),
                'peak_days' => $this->identifyPeakDays($calendar),
                'optimal_booking_windows' => $this->identifyOptimalWindows($calendar),
            ],
        ];
    }

    /**
     * Calculate demand-based pricing multiplier
     */
    private function calculateDemandMultiplier(Billboard $billboard, array $options): float
    {
        $baseMultiplier = 1.0;

        // Recent booking activity (last 30 days)
        $recentBookings = $billboard->bookings()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Booking inquiries vs successful bookings
        $inquiryRate = $this->getInquiryRate($billboard);

        // Current availability vs demand
        $availabilityScore = $this->calculateAvailabilityScore($billboard, $options);

        // Seasonal demand patterns
        $seasonalDemand = $this->getSeasonalDemand($options['date'] ?? now()->toDateString());

        // Calculate weighted demand score
        $demandScore =
            ($recentBookings * 0.3) +
            ($inquiryRate * 0.3) +
            ($availabilityScore * 0.2) +
            ($seasonalDemand * 0.2);

        // Convert to multiplier (1.0 = normal, >1.0 = high demand, <1.0 = low demand)
        if ($demandScore > 10) {
            return min(1.4, 1.0 + ($demandScore - 10) * 0.02);
        } elseif ($demandScore < 3) {
            return max(0.8, 1.0 - (3 - $demandScore) * 0.03);
        }

        return $baseMultiplier;
    }

    /**
     * Calculate seasonal pricing adjustments
     */
    private function calculateSeasonalMultiplier(array $options): float
    {
        $date = Carbon::parse($options['date'] ?? now());
        $month = $date->month;
        $dayOfWeek = $date->dayOfWeek;

        // Monthly seasonal adjustments (Malawian context)
        $monthlyMultipliers = [
            1 => 0.9,  // January (post-holiday slow period)
            2 => 0.95, // February
            3 => 1.1,  // March (back-to-school)
            4 => 1.0,  // April
            5 => 1.05, // May
            6 => 0.9,  // June (mid-year slow period)
            7 => 0.95, // July
            8 => 1.1,  // August (back-to-school preparation)
            9 => 1.15, // September (peak business activity)
            10 => 1.2, // October (pre-holiday business)
            11 => 1.25, // November (holiday preparation)
            12 => 1.3, // December (holiday season peak)
        ];

        // Day of week adjustments
        $dayMultipliers = [
            0 => 0.8,  // Sunday
            1 => 1.0,  // Monday
            2 => 1.05, // Tuesday
            3 => 1.1,  // Wednesday
            4 => 1.1,  // Thursday
            5 => 1.05, // Friday
            6 => 0.9,  // Saturday
        ];

        // Special events and holidays
        $eventMultiplier = $this->getEventMultiplier($date);

        return $monthlyMultipliers[$month] * $dayMultipliers[$dayOfWeek] * $eventMultiplier;
    }

    /**
     * Calculate location-based pricing
     */
    private function calculateLocationMultiplier(Billboard $billboard): float
    {
        $baseMultiplier = 1.0;

        // Area type multipliers
        $areaMultipliers = [
            'highway' => 1.3,
            'commercial' => 1.2,
            'mixed' => 1.0,
            'residential' => 0.8,
            'industrial' => 0.9,
        ];

        $areaType = $billboard->area_type ?? 'mixed';
        $areaMultiplier = $areaMultipliers[$areaType] ?? 1.0;

        // Traffic volume impact (simulated)
        $trafficVolume = $this->getTrafficVolume($billboard);
        $trafficMultiplier = 1.0 + (($trafficVolume - 5000) / 10000) * 0.2;
        $trafficMultiplier = max(0.8, min(1.4, $trafficMultiplier));

        // Proximity to landmarks/popular areas
        $proximityMultiplier = $this->getProximityMultiplier($billboard);

        return $areaMultiplier * $trafficMultiplier * $proximityMultiplier;
    }

    /**
     * Calculate performance-based pricing
     */
    private function calculatePerformanceMultiplier($state): float
    {
        $baseMultiplier = 1.0;

        // Occupancy rate impact
        $occupancyRate = $state->occupancy_rate;
        if ($occupancyRate > 80) {
            $baseMultiplier *= 1.15;
        } elseif ($occupancyRate > 60) {
            $baseMultiplier *= 1.05;
        } elseif ($occupancyRate < 30) {
            $baseMultiplier *= 0.9;
        }

        // Booking success rate
        $totalBookings = $state->total_bookings;
        if ($totalBookings > 50) {
            $baseMultiplier *= 1.1;
        } elseif ($totalBookings < 10) {
            $baseMultiplier *= 0.95;
        }

        // Revenue generation efficiency
        $avgBookingValue = $state->average_booking_value;
        $expectedValue = $this->calculateExpectedBookingValue($state);

        if ($avgBookingValue > $expectedValue * 1.2) {
            $baseMultiplier *= 1.05;
        } elseif ($avgBookingValue < $expectedValue * 0.8) {
            $baseMultiplier *= 0.95;
        }

        return $baseMultiplier;
    }

    /**
     * Calculate competition-based pricing
     */
    private function calculateCompetitionMultiplier(Billboard $billboard): float
    {
        // Get nearby competing billboards
        $competitors = $this->getNearbyCompetitors($billboard);

        if ($competitors->isEmpty()) {
            return 1.1; // Premium for no competition
        }

        $myPrice = $billboard->price;
        $avgCompetitorPrice = $competitors->avg('price');

        if ($myPrice > $avgCompetitorPrice * 1.2) {
            return 0.9; // Reduce price if significantly higher
        } elseif ($myPrice < $avgCompetitorPrice * 0.8) {
            return 1.1; // Can increase price if significantly lower
        }

        return 1.0; // Neutral if in competitive range
    }

    /**
     * Calculate urgency-based pricing
     */
    private function calculateUrgencyMultiplier(array $options): float
    {
        if (!isset($options['booking_date'])) {
            return 1.0;
        }

        $bookingDate = Carbon::parse($options['booking_date']);
        $daysUntilBooking = now()->diffInDays($bookingDate);

        // Last-minute bookings (within 7 days) get premium
        if ($daysUntilBooking <= 7) {
            return 1.2;
        }

        // Very advance bookings (over 90 days) get discount
        if ($daysUntilBooking > 90) {
            return 0.95;
        }

        return 1.0;
    }

    /**
     * Calculate pricing confidence based on data quality and market factors
     */
    private function calculatePricingConfidence(Billboard $billboard, array $factors): float
    {
        $confidence = 0.5; // Base confidence

        // Data quality factors
        $bookingHistory = $billboard->bookings()->count();
        if ($bookingHistory > 20) {
            $confidence += 0.2;
        } elseif ($bookingHistory > 5) {
            $confidence += 0.1;
        }

        // Market stability
        $priceVolatility = $this->calculateMarketVolatility($billboard);
        if ($priceVolatility < 0.1) {
            $confidence += 0.15;
        }

        // Factor consistency
        $factorVariance = $this->calculateFactorVariance($factors);
        if ($factorVariance < 0.2) {
            $confidence += 0.15;
        }

        return min(1.0, $confidence);
    }

    /**
     * Generate actionable pricing recommendations
     */
    private function generatePricingRecommendations(Billboard $billboard, float $dynamicPrice, float $basePrice): array
    {
        $recommendations = [];

        $priceChange = ($dynamicPrice - $basePrice) / $basePrice;

        if ($priceChange > 0.1) {
            $recommendations[] = [
                'type' => 'increase',
                'message' => 'Consider increasing price by ' . round($priceChange * 100, 1) . '% due to high demand',
                'urgency' => 'medium',
                'potential_revenue_impact' => '+' . round($priceChange * $basePrice * 30, 2), // 30-day impact
            ];
        } elseif ($priceChange < -0.1) {
            $recommendations[] = [
                'type' => 'decrease',
                'message' => 'Consider reducing price by ' . round(abs($priceChange) * 100, 1) . '% to increase bookings',
                'urgency' => 'low',
                'potential_booking_impact' => '+' . round(abs($priceChange) * 100 * 0.5, 1) . '% more bookings',
            ];
        }

        // Add timing recommendations
        $optimalTimes = $this->getOptimalPricingTimes($billboard);
        if (!empty($optimalTimes)) {
            $recommendations[] = [
                'type' => 'timing',
                'message' => 'Best times for premium pricing: ' . implode(', ', $optimalTimes),
                'urgency' => 'info',
            ];
        }

        return $recommendations;
    }

    // Additional helper methods would go here...
    private function getInquiryRate(Billboard $billboard): float
    {
        // Simulate inquiry rate - in production this would come from actual data
        return rand(50, 150) / 10; // 5.0 to 15.0
    }

    private function calculateAvailabilityScore(Billboard $billboard, array $options): float
    {
        // Simulate availability scoring
        return rand(30, 100) / 10; // 3.0 to 10.0
    }

    private function getSeasonalDemand(string $date): float
    {
        $month = Carbon::parse($date)->month;
        $seasonalScores = [1 => 0.8, 2 => 0.9, 3 => 1.1, 4 => 1.0, 5 => 1.0, 6 => 0.9,
                          7 => 0.9, 8 => 1.1, 9 => 1.2, 10 => 1.3, 11 => 1.4, 12 => 1.5];
        return ($seasonalScores[$month] ?? 1.0) * 10;
    }

    private function getEventMultiplier(Carbon $date): float
    {
        // Check for special events, holidays, etc.
        // This would integrate with a calendar API or database
        return 1.0;
    }

    private function getTrafficVolume(Billboard $billboard): int
    {
        // Simulate traffic volume - in production this would come from traffic APIs
        return rand(2000, 20000);
    }

    private function getProximityMultiplier(Billboard $billboard): float
    {
        // Simulate proximity to landmarks - in production this would use geospatial data
        return rand(90, 120) / 100; // 0.9 to 1.2
    }

    private function calculateExpectedBookingValue($state): float
    {
        return $state->total_revenue > 0 ? $state->total_revenue / max(1, $state->total_bookings) : 1000;
    }

    private function getNearbyCompetitors(Billboard $billboard): Collection
    {
        // In production, this would use geospatial queries
        return $billboard->tenant->billboards()
            ->where('id', '!=', $billboard->id)
            ->where('size', $billboard->size)
            ->limit(5)
            ->get();
    }

    private function calculateMarketVolatility(Billboard $billboard): float
    {
        return rand(5, 30) / 100; // 0.05 to 0.30
    }

    private function calculateFactorVariance(array $factors): float
    {
        $values = array_values($factors);
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    private function getOptimalPricingTimes(Billboard $billboard): array
    {
        return ['Holiday Season', 'Back-to-School', 'Election Period'];
    }

    // ... Additional helper methods for market data, trends analysis, etc.
}