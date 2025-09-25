<?php

namespace App\Events;

use App\Models\Tenant;
use App\States\TenantState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class TenantSubscriptionUpgraded extends Event
{
    #[StateId(TenantState::class)]
    public int $tenant_id;

    public function __construct(
        int $tenant_id,
        public string $from_tier,
        public string $to_tier,
        public float $monthly_amount,
        public array $payment_details = [],
    ) {
        $this->tenant_id = $tenant_id;
    }

    public function validate(TenantState $state): void
    {
        $this->assert($state->is_active, 'Tenant must be active to upgrade subscription');
        $this->assert($state->subscription_tier !== $this->to_tier, 'Tenant is already on this tier');
    }

    public function apply(TenantState $state): void
    {
        $state->subscription_tier = $this->to_tier;
        $state->monthly_revenue = $this->monthly_amount;

        // Update limits based on new tier
        $limits = $this->getSubscriptionLimits($this->to_tier);
        $state->user_limit = $limits['users'];
        $state->billboard_limit = $limits['billboards'];

        // Update feature flags based on tier
        $state->feature_flags = array_merge($state->feature_flags, $this->getTierFeatures($this->to_tier));

        // Extend subscription
        if ($this->to_tier !== Tenant::TIER_TRIAL) {
            $state->trial_ends_at = null; // Remove trial limitations
        }
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenant_id);
        if ($tenant) {
            $tenant->update([
                'subscription_tier' => $this->to_tier,
                'monthly_revenue' => $this->monthly_amount,
                'feature_flags' => array_merge($tenant->feature_flags ?? [], $this->getTierFeatures($this->to_tier)),
                'user_limit' => $this->getSubscriptionLimits($this->to_tier)['users'],
                'billboard_limit' => $this->getSubscriptionLimits($this->to_tier)['billboards'],
                'trial_ends_at' => $this->to_tier === Tenant::TIER_TRIAL ? $tenant->trial_ends_at : null,
                'subscription_ends_at' => now()->addMonth(),
            ]);

            // Log the upgrade
            activity()
                ->performedOn($tenant)
                ->withProperties([
                    'from_tier' => $this->from_tier,
                    'to_tier' => $this->to_tier,
                    'monthly_amount' => $this->monthly_amount,
                    'payment_details' => $this->payment_details,
                ])
                ->log('Subscription upgraded');
        }
    }

    private function getSubscriptionLimits(string $tier): array
    {
        return match ($tier) {
            Tenant::TIER_TRIAL => ['users' => 3, 'billboards' => 10],
            Tenant::TIER_STARTER => ['users' => 10, 'billboards' => 100],
            Tenant::TIER_PROFESSIONAL => ['users' => 50, 'billboards' => 500],
            Tenant::TIER_ENTERPRISE => ['users' => -1, 'billboards' => -1],
            default => ['users' => 3, 'billboards' => 10],
        };
    }

    private function getTierFeatures(string $tier): array
    {
        $features = [];

        if ($tier !== Tenant::TIER_TRIAL) {
            $features['advanced_analytics'] = true;
            $features['priority_support'] = true;
        }

        if (in_array($tier, [Tenant::TIER_PROFESSIONAL, Tenant::TIER_ENTERPRISE])) {
            $features['api_access'] = true;
            $features['custom_integrations'] = true;
            $features['booking_automation'] = true;
        }

        if ($tier === Tenant::TIER_ENTERPRISE) {
            $features['white_labeling'] = true;
            $features['custom_domain_enabled'] = true;
        }

        return $features;
    }

    public function fired(): void
    {
        // Send upgrade confirmation email
        TenantSubscriptionConfirmationSent::fire(
            tenant_id: $this->tenant_id,
            tier: $this->to_tier,
            amount: $this->monthly_amount
        );

        // Unlock new features notification
        TenantFeaturesUnlocked::fire(
            tenant_id: $this->tenant_id,
            new_features: array_keys($this->getTierFeatures($this->to_tier))
        );
    }
}