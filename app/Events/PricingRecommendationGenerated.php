<?php

namespace App\Events;

use App\Models\Billboard;
use App\States\BillboardState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class PricingRecommendationGenerated extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    #[StateId(BillboardState::class)]
    public int $billboard_id;

    public function __construct(
        int $billboard_id,
        public float $current_price,
        public float $recommended_price,
        public float $confidence_score,
        public array $pricing_factors,
        public array $recommendations,
        public string $market_position,
        public ?string $next_review_date = null,
    ) {
        $this->billboard_id = $billboard_id;
    }

    public function apply(BillboardState $state): void
    {
        // Update billboard state with pricing insights
        $state->suggested_price = $this->recommended_price;
        $state->price_history[] = [
            'date' => now()->toISOString(),
            'old_price' => $this->current_price,
            'suggested_price' => $this->recommended_price,
            'confidence' => $this->confidence_score,
            'factors' => $this->pricing_factors,
        ];

        // Keep only last 30 pricing records
        $state->price_history = array_slice($state->price_history, -30);

        // Update demand patterns based on pricing factors
        $today = now()->format('Y-m-d');
        $state->demand_patterns[$today] = [
            'demand_score' => $this->pricing_factors['demand_multiplier'],
            'seasonal_impact' => $this->pricing_factors['seasonal_multiplier'],
            'competition_impact' => $this->pricing_factors['competition_multiplier'],
        ];
    }

    public function handle(): void
    {
        $billboard = Billboard::find($this->billboard_id);
        if (!$billboard) return;

        // Log pricing recommendation
        activity()
            ->performedOn($billboard)
            ->withProperties([
                'current_price' => $this->current_price,
                'recommended_price' => $this->recommended_price,
                'confidence_score' => $this->confidence_score,
                'price_change_percentage' => round((($this->recommended_price - $this->current_price) / $this->current_price) * 100, 2),
                'recommendations' => $this->recommendations,
                'market_position' => $this->market_position,
            ])
            ->log('Pricing recommendation generated');

        // Create notification for significant price changes
        $priceChangePercentage = abs(($this->recommended_price - $this->current_price) / $this->current_price) * 100;

        if ($priceChangePercentage >= 10) {
            $this->createPricingAlert($billboard, $priceChangePercentage);
        }

        // Update billboard's suggested price if confidence is high
        if ($this->confidence_score >= 0.8 && $priceChangePercentage >= 5) {
            $billboard->update(['suggested_price' => $this->recommended_price]);
        }
    }

    public function broadcastOn(): array
    {
        $billboard = Billboard::find($this->billboard_id);
        $tenantId = $billboard->tenant_id;

        return [
            new PrivateChannel("tenant.{$tenantId}.pricing"),
            new PrivateChannel("billboard.{$this->billboard_id}.pricing"),
            new PrivateChannel("tenant.{$tenantId}.dashboard"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pricing.recommendation.generated';
    }

    public function broadcastWith(): array
    {
        $billboard = Billboard::find($this->billboard_id);

        return [
            'billboard_id' => $this->billboard_id,
            'billboard' => [
                'id' => $billboard->id,
                'name' => $billboard->name,
                'location' => $billboard->location,
                'current_price' => $this->current_price,
            ],
            'pricing' => [
                'recommended_price' => $this->recommended_price,
                'price_change_percentage' => round((($this->recommended_price - $this->current_price) / $this->current_price) * 100, 2),
                'confidence_score' => $this->confidence_score,
                'market_position' => $this->market_position,
            ],
            'recommendations' => $this->recommendations,
            'timestamp' => now()->toISOString(),
        ];
    }

    private function createPricingAlert(Billboard $billboard, float $priceChangePercentage): void
    {
        $alertType = $this->recommended_price > $this->current_price ? 'price_increase_opportunity' : 'price_reduction_suggested';

        \App\Events\RealTimeNotificationSent::dispatch(
            tenant_id: $billboard->tenant_id,
            type: $alertType,
            title: 'Pricing Recommendation',
            message: "Consider " . ($this->recommended_price > $this->current_price ? 'increasing' : 'decreasing') .
                    " price for {$billboard->name} by {$priceChangePercentage}%",
            data: [
                'billboard_id' => $billboard->id,
                'billboard_name' => $billboard->name,
                'current_price' => $this->current_price,
                'recommended_price' => $this->recommended_price,
                'confidence_score' => $this->confidence_score,
            ],
            priority: $this->confidence_score >= 0.8 ? 'high' : 'normal',
            action_url: route('tenant.admin.billboards.edit', ['tenant' => $billboard->tenant->uuid, 'billboard' => $billboard->id]),
            action_buttons: [
                ['label' => 'Review Pricing', 'action' => 'review_pricing', 'style' => 'primary'],
                ['label' => 'Apply Suggestion', 'action' => 'apply_pricing', 'style' => 'success'],
            ]
        );
    }
}