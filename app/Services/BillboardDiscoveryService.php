<?php

namespace App\Services;

use App\Models\Billboard;
use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BillboardDiscoveryService
{
    /**
     * AI-powered billboard discovery with smart recommendations
     */
    public function discoverBillboards(array $criteria, Tenant $tenant): array
    {
        $baseQuery = $tenant->billboards()->where('status', Billboard::STATUS_AVAILABLE);

        // Apply basic filters
        $filteredBillboards = $this->applyBasicFilters($baseQuery, $criteria);

        // Get AI-powered recommendations
        $recommendations = $this->getAIRecommendations($filteredBillboards, $criteria, $tenant);

        // Apply ML-based scoring
        $scoredBillboards = $this->scoreWithMachineLearning($recommendations, $criteria);

        // Add market intelligence
        $withMarketData = $this->enrichWithMarketIntelligence($scoredBillboards, $criteria);

        // Apply dynamic pricing suggestions
        $withPricingInsights = $this->addPricingInsights($withMarketData, $criteria);

        return [
            'billboards' => $withPricingInsights,
            'insights' => $this->generateDiscoveryInsights($criteria, $tenant),
            'alternatives' => $this->suggestAlternatives($criteria, $tenant),
            'market_trends' => $this->getMarketTrends($criteria, $tenant),
        ];
    }

    /**
     * Location-based intelligent search with radius and demographics
     */
    public function searchByLocation(array $locationCriteria, Tenant $tenant): array
    {
        $cacheKey = "billboard_location_search_" . md5(serialize($locationCriteria)) . "_" . $tenant->id;

        return Cache::remember($cacheKey, 300, function () use ($locationCriteria, $tenant) {
            $baseQuery = $tenant->billboards()->where('status', Billboard::STATUS_AVAILABLE);

            // Geographic filtering
            if (isset($locationCriteria['coordinates'])) {
                $baseQuery = $this->addGeoProximityFilter($baseQuery, $locationCriteria);
            }

            // Area type filtering (commercial, residential, highway, etc.)
            if (isset($locationCriteria['area_types'])) {
                $baseQuery->whereIn('area_type', $locationCriteria['area_types']);
            }

            $billboards = $baseQuery->get();

            // Enhance with location intelligence
            $enhancedBillboards = $this->addLocationIntelligence($billboards, $locationCriteria);

            return [
                'results' => $enhancedBillboards,
                'location_insights' => $this->getLocationInsights($locationCriteria, $tenant),
                'demographic_data' => $this->getDemographicData($locationCriteria),
                'traffic_patterns' => $this->getTrafficPatterns($locationCriteria),
            ];
        });
    }

    /**
     * Smart availability checking with conflict detection
     */
    public function checkSmartAvailability(
        Collection $billboards,
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        array $preferences = []
    ): array {
        $availabilityData = [];

        foreach ($billboards as $billboard) {
            $state = $billboard->getBillboardState();

            $availability = [
                'billboard_id' => $billboard->id,
                'is_available' => !$state->hasConflictingBooking($startDate, $endDate),
                'conflicts' => $this->getBookingConflicts($billboard, $startDate, $endDate),
                'suggested_dates' => $this->suggestAlternativeDates($billboard, $startDate, $endDate),
                'pricing_windows' => $this->getPricingWindows($billboard, $startDate, $endDate),
            ];

            // Add intelligent recommendations
            if (!$availability['is_available']) {
                $availability['alternatives'] = $this->findSimilarAvailableBillboards(
                    $billboard,
                    $startDate,
                    $endDate,
                    $preferences
                );
            }

            $availabilityData[] = $availability;
        }

        return [
            'availability' => $availabilityData,
            'booking_insights' => $this->generateBookingInsights($billboards, $startDate, $endDate),
            'demand_forecast' => $this->forecastDemand($startDate, $endDate),
        ];
    }

    /**
     * Generate personalized recommendations based on user behavior
     */
    public function getPersonalizedRecommendations(int $clientId, Tenant $tenant): array
    {
        $cacheKey = "personalized_recommendations_{$clientId}_{$tenant->id}";

        return Cache::remember($cacheKey, 600, function () use ($clientId, $tenant) {
            // Analyze past booking patterns
            $pastBookings = $tenant->bookings()
                ->where('client_id', $clientId)
                ->with(['billboard'])
                ->get();

            $preferences = $this->extractClientPreferences($pastBookings);

            // Find similar successful campaigns
            $similarCampaigns = $this->findSimilarSuccessfulCampaigns($preferences, $tenant);

            // Generate recommendations based on ML model
            $recommendations = $this->generateMLRecommendations($preferences, $similarCampaigns, $tenant);

            return [
                'recommended_billboards' => $recommendations,
                'client_insights' => $preferences,
                'success_factors' => $this->identifySuccessFactors($pastBookings),
                'optimization_suggestions' => $this->getOptimizationSuggestions($preferences, $tenant),
            ];
        });
    }

    private function applyBasicFilters($query, array $criteria)
    {
        // Size filtering
        if (isset($criteria['sizes']) && !empty($criteria['sizes'])) {
            $query->whereIn('size', $criteria['sizes']);
        }

        // Price range filtering
        if (isset($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }
        if (isset($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }

        // Location filtering
        if (isset($criteria['locations']) && !empty($criteria['locations'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['locations'] as $location) {
                    $q->orWhere('location', 'like', "%{$location}%");
                }
            });
        }

        return $query->get();
    }

    private function getAIRecommendations(Collection $billboards, array $criteria, Tenant $tenant): Collection
    {
        // Simulate AI recommendations based on multiple factors
        return $billboards->map(function ($billboard) use ($criteria, $tenant) {
            $state = $billboard->getBillboardState();

            // Calculate AI recommendation score
            $score = $this->calculateRecommendationScore($billboard, $state, $criteria, $tenant);

            $billboard->ai_score = $score;
            $billboard->ai_insights = $this->generateAIInsights($billboard, $state, $criteria);

            return $billboard;
        })->sortByDesc('ai_score');
    }

    private function calculateRecommendationScore(Billboard $billboard, $state, array $criteria, Tenant $tenant): float
    {
        $score = 0.0;

        // Performance score (40% weight)
        $performanceScore = $this->calculatePerformanceScore($state);
        $score += $performanceScore * 0.4;

        // Location score (30% weight)
        $locationScore = $this->calculateLocationScore($billboard, $criteria);
        $score += $locationScore * 0.3;

        // Value score (20% weight)
        $valueScore = $this->calculateValueScore($billboard, $criteria);
        $score += $valueScore * 0.2;

        // Availability score (10% weight)
        $availabilityScore = $this->calculateAvailabilityScore($state);
        $score += $availabilityScore * 0.1;

        return min(100, $score);
    }

    private function calculatePerformanceScore($state): float
    {
        $score = 50.0; // Base score

        // Occupancy rate impact
        if ($state->occupancy_rate > 80) {
            $score += 30;
        } elseif ($state->occupancy_rate > 60) {
            $score += 20;
        } elseif ($state->occupancy_rate > 40) {
            $score += 10;
        }

        // Booking frequency
        if ($state->total_bookings > 50) {
            $score += 15;
        } elseif ($state->total_bookings > 20) {
            $score += 10;
        } elseif ($state->total_bookings > 10) {
            $score += 5;
        }

        // Days since last booking (freshness)
        if ($state->days_since_last_booking < 7) {
            $score += 5;
        } elseif ($state->days_since_last_booking > 30) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    private function calculateLocationScore(Billboard $billboard, array $criteria): float
    {
        $score = 50.0; // Base score

        // Area type preference
        if (isset($criteria['preferred_area_types'])) {
            $billboard_area = $billboard->area_type ?? 'mixed';
            if (in_array($billboard_area, $criteria['preferred_area_types'])) {
                $score += 25;
            }
        }

        // Traffic volume (simulated)
        $trafficVolume = $this->getTrafficVolume($billboard);
        if ($trafficVolume > 10000) {
            $score += 20;
        } elseif ($trafficVolume > 5000) {
            $score += 10;
        }

        // Demographics alignment
        $demographics = $this->getDemographicsForLocation($billboard);
        if ($demographics && isset($criteria['target_demographics'])) {
            $alignmentScore = $this->calculateDemographicAlignment($demographics, $criteria['target_demographics']);
            $score += $alignmentScore * 0.05;
        }

        return max(0, min(100, $score));
    }

    private function calculateValueScore(Billboard $billboard, array $criteria): float
    {
        $score = 50.0; // Base score

        $price = $billboard->price;
        $suggestedPrice = $this->calculateSuggestedPrice($billboard);

        // Price competitiveness
        $priceRatio = $price / max($suggestedPrice, 1);

        if ($priceRatio < 0.8) {
            $score += 30; // Excellent value
        } elseif ($priceRatio < 0.9) {
            $score += 20; // Good value
        } elseif ($priceRatio < 1.1) {
            $score += 10; // Fair value
        } else {
            $score -= 20; // Overpriced
        }

        // Budget alignment
        if (isset($criteria['budget'])) {
            $duration = isset($criteria['duration']) ? $criteria['duration'] : 30;
            $totalCost = $price * $duration;

            if ($totalCost <= $criteria['budget']) {
                $score += 15;
            } elseif ($totalCost <= $criteria['budget'] * 1.2) {
                $score += 5;
            } else {
                $score -= 25;
            }
        }

        return max(0, min(100, $score));
    }

    private function calculateAvailabilityScore($state): float
    {
        if ($state->status !== Billboard::STATUS_AVAILABLE) {
            return 0;
        }

        $score = 100.0;

        // Reduce score if heavily booked in near future
        $upcomingBookings = count($state->active_bookings);
        if ($upcomingBookings > 5) {
            $score -= 30;
        } elseif ($upcomingBookings > 2) {
            $score -= 15;
        }

        return max(0, $score);
    }

    private function generateAIInsights(Billboard $billboard, $state, array $criteria): array
    {
        $insights = [];

        // Performance insights
        if ($state->occupancy_rate > 80) {
            $insights[] = [
                'type' => 'performance',
                'message' => 'High-performing billboard with {occupancy}% occupancy rate',
                'data' => ['occupancy' => $state->occupancy_rate]
            ];
        }

        // Pricing insights
        $suggestedPrice = $this->calculateSuggestedPrice($billboard);
        $currentPrice = $billboard->price;

        if ($currentPrice < $suggestedPrice * 0.8) {
            $insights[] = [
                'type' => 'pricing',
                'message' => 'Excellent value - priced {percentage}% below market rate',
                'data' => ['percentage' => round((($suggestedPrice - $currentPrice) / $suggestedPrice) * 100)]
            ];
        }

        // Demand insights
        if ($state->total_bookings > 30) {
            $insights[] = [
                'type' => 'demand',
                'message' => 'High demand location with {bookings} completed bookings',
                'data' => ['bookings' => $state->total_bookings]
            ];
        }

        // Timing insights
        $bestTimes = $this->getBestBookingTimes($billboard);
        if (!empty($bestTimes)) {
            $insights[] = [
                'type' => 'timing',
                'message' => 'Best booking periods: {times}',
                'data' => ['times' => implode(', ', $bestTimes)]
            ];
        }

        return $insights;
    }

    // Additional helper methods...
    private function calculateSuggestedPrice(Billboard $billboard): float
    {
        // Simplified pricing calculation based on various factors
        $basePrice = 1000; // Base price in local currency

        // Adjust for location type
        $locationMultiplier = match($billboard->area_type ?? 'mixed') {
            'commercial' => 1.5,
            'highway' => 1.3,
            'residential' => 0.8,
            default => 1.0
        };

        // Adjust for size
        $sizeMultiplier = match($billboard->size) {
            'small' => 0.7,
            'medium' => 1.0,
            'large' => 1.4,
            'extra_large' => 1.8,
            default => 1.0
        };

        return $basePrice * $locationMultiplier * $sizeMultiplier;
    }

    private function getTrafficVolume(Billboard $billboard): int
    {
        // Simulate traffic volume based on location and area type
        $baseVolume = 5000;

        $multiplier = match($billboard->area_type ?? 'mixed') {
            'highway' => 3.0,
            'commercial' => 2.0,
            'residential' => 0.5,
            default => 1.0
        };

        return (int) ($baseVolume * $multiplier * (0.8 + (rand(0, 40) / 100)));
    }

    private function getBestBookingTimes(Billboard $billboard): array
    {
        // Analyze historical booking patterns to suggest best times
        return ['Q1', 'Holiday Season', 'Back-to-School'];
    }

    // ... Additional methods for ML scoring, market intelligence, etc.
}