<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenant_id,
        public array $metrics,
        public string $metric_type = 'general', // general, revenue, bookings, billboards
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenant_id}.dashboard"),
            new PrivateChannel("tenant.{$this->tenant_id}.metrics"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.metrics.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'metric_type' => $this->metric_type,
            'metrics' => $this->metrics,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Calculate and broadcast comprehensive dashboard metrics
     */
    public static function broadcastTenantMetrics(int $tenant_id): void
    {
        $tenant = Tenant::find($tenant_id);
        if (!$tenant) return;

        // General metrics
        $generalMetrics = [
            'total_billboards' => $tenant->billboards()->count(),
            'available_billboards' => $tenant->billboards()->where('status', 'available')->count(),
            'occupied_billboards' => $tenant->billboards()->where('status', 'occupied')->count(),
            'total_clients' => $tenant->clients()->count(),
            'total_users' => $tenant->users()->count(),
            'setup_completion' => $tenant->getOnboardingProgress(),
        ];

        static::dispatch($tenant_id, $generalMetrics, 'general');

        // Revenue metrics
        $revenueMetrics = [
            'total_revenue' => $tenant->total_revenue,
            'monthly_revenue' => $tenant->monthly_revenue,
            'pending_revenue' => $tenant->bookings()
                ->where('status', 'confirmed')
                ->sum('final_price'),
            'avg_booking_value' => $tenant->bookings()
                ->where('status', 'completed')
                ->avg('final_price') ?? 0,
        ];

        static::dispatch($tenant_id, $revenueMetrics, 'revenue');

        // Booking metrics
        $bookingMetrics = [
            'total_bookings' => $tenant->total_bookings,
            'pending_bookings' => $tenant->bookings()->where('status', 'requested')->count(),
            'confirmed_bookings' => $tenant->bookings()->where('status', 'confirmed')->count(),
            'completed_bookings' => $tenant->bookings()->where('status', 'completed')->count(),
            'monthly_bookings' => $tenant->bookings()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        static::dispatch($tenant_id, $bookingMetrics, 'bookings');

        // Billboard performance metrics
        $billboardMetrics = [];
        $tenant->billboards()->chunk(100, function ($billboards) use (&$billboardMetrics) {
            foreach ($billboards as $billboard) {
                $state = $billboard->getBillboardState();
                $billboardMetrics[] = [
                    'id' => $billboard->id,
                    'name' => $billboard->name,
                    'occupancy_rate' => $state->occupancy_rate,
                    'total_revenue' => $state->total_revenue,
                    'total_bookings' => $state->total_bookings,
                    'status' => $state->status,
                ];
            }
        });

        static::dispatch($tenant_id, ['billboards' => $billboardMetrics], 'billboard_performance');
    }
}