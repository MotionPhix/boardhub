<?php

namespace App\Events;

use App\Models\Tenant;
use App\States\TenantState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class TenantUsageTracked extends Event
{
    #[StateId(TenantState::class)]
    public int $tenant_id;

    public function __construct(
        int $tenant_id,
        public string $action,
        public array $metadata = [],
    ) {
        $this->tenant_id = $tenant_id;
    }

    public function apply(TenantState $state): void
    {
        // Update usage statistics
        $stats = $state->usage_stats ?? [];
        $stats['last_activity'] = now()->toISOString();
        $stats['actions'] = $stats['actions'] ?? [];

        // Track specific action
        $actionKey = $this->action;
        $stats['actions'][$actionKey] = ($stats['actions'][$actionKey] ?? 0) + 1;

        // Update monthly totals
        $month = now()->format('Y-m');
        $stats['monthly'][$month] = $stats['monthly'][$month] ?? [];
        $stats['monthly'][$month][$actionKey] = ($stats['monthly'][$month][$actionKey] ?? 0) + 1;

        $state->usage_stats = $stats;
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenant_id);
        if ($tenant) {
            // Update last activity
            $tenant->update(['last_activity_at' => now()]);

            // Handle specific actions
            match ($this->action) {
                'billboard_created' => $this->handleBillboardCreated($tenant),
                'booking_created' => $this->handleBookingCreated($tenant),
                'client_created' => $this->handleClientCreated($tenant),
                'user_invited' => $this->handleUserInvited($tenant),
                'revenue_generated' => $this->handleRevenueGenerated($tenant),
                default => null,
            };

            // Update comprehensive usage stats
            $tenant->updateUsageStats();
        }
    }

    private function handleBillboardCreated(Tenant $tenant): void
    {
        // Check if this is their first billboard
        if ($tenant->billboards()->count() === 1) {
            TenantOnboardingStepCompleted::fire(
                tenant_id: $this->tenant_id,
                step: 'first_billboard_added'
            );
        }

        // Check limits
        if (!$tenant->isWithinLimits('billboards')) {
            TenantLimitExceeded::fire(
                tenant_id: $this->tenant_id,
                limit_type: 'billboards',
                current_count: $tenant->billboards()->count()
            );
        }
    }

    private function handleBookingCreated(Tenant $tenant): void
    {
        // Check if this is their first booking
        if ($tenant->bookings()->count() === 1) {
            TenantOnboardingStepCompleted::fire(
                tenant_id: $this->tenant_id,
                step: 'first_booking_created'
            );
        }

        // Update monthly booking count
        $monthlyBookings = $tenant->bookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if (!$tenant->isWithinLimits('bookings_per_month')) {
            TenantLimitExceeded::fire(
                tenant_id: $this->tenant_id,
                limit_type: 'bookings_per_month',
                current_count: $monthlyBookings
            );
        }
    }

    private function handleClientCreated(Tenant $tenant): void
    {
        // Check if this is their first client
        if ($tenant->clients()->count() === 1) {
            TenantOnboardingStepCompleted::fire(
                tenant_id: $this->tenant_id,
                step: 'first_client_added'
            );
        }
    }

    private function handleUserInvited(Tenant $tenant): void
    {
        // Check if they've invited team members
        if ($tenant->users()->count() > 1) {
            TenantOnboardingStepCompleted::fire(
                tenant_id: $this->tenant_id,
                step: 'team_invited'
            );
        }

        // Check user limits
        if (!$tenant->isWithinLimits('users')) {
            TenantLimitExceeded::fire(
                tenant_id: $this->tenant_id,
                limit_type: 'users',
                current_count: $tenant->users()->count()
            );
        }
    }

    private function handleRevenueGenerated(Tenant $tenant): void
    {
        $amount = $this->metadata['amount'] ?? 0;
        $tenant->increment('total_revenue', $amount);

        // Update monthly revenue
        $monthlyRevenue = $tenant->bookings()
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('final_price');

        $tenant->update(['monthly_revenue' => $monthlyRevenue]);
    }
}