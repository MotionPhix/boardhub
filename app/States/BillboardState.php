<?php

namespace App\States;

use Carbon\Carbon;
use Thunk\Verbs\State;

class BillboardState extends State
{
    public string $status = 'available'; // available, occupied, maintenance
    public array $pending_bookings = [];
    public array $active_bookings = [];
    public ?Carbon $last_booked_at = null;
    public float $total_revenue = 0.0;
    public int $total_bookings = 0;

    // Enhanced analytics and tracking
    public int $total_views = 0;
    public array $monthly_stats = [];
    public array $availability_history = [];
    public ?string $last_status_change = null;
    public ?array $current_booking = null;

    // Performance metrics
    public float $occupancy_rate = 0.0;
    public float $average_booking_value = 0.0;
    public int $days_since_last_booking = 0;

    // AI-powered insights
    public float $suggested_price = 0.0;
    public array $price_history = [];
    public array $demand_patterns = [];

    // Location intelligence
    public ?array $coordinates = null;
    public ?string $area_type = null; // commercial, residential, highway, etc.
    public array $nearby_competitors = [];
    public array $demographic_data = [];

    /**
     * Check if the billboard has conflicting bookings for the given period
     */
    public function hasConflictingBooking(Carbon $start_date, Carbon $end_date): bool
    {
        // Check active bookings for conflicts
        foreach ($this->active_bookings as $booking) {
            $booking_start = Carbon::parse($booking['start_date']);
            $booking_end = Carbon::parse($booking['end_date']);

            // Check for date overlap
            if ($start_date->lessThan($booking_end) && $end_date->greaterThan($booking_start)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a pending booking to the billboard
     */
    public function addPendingBooking(string $booking_id, Carbon $start_date, Carbon $end_date): void
    {
        $this->pending_bookings[] = [
            'booking_id' => $booking_id,
            'start_date' => $start_date->toISOString(),
            'end_date' => $end_date->toISOString(),
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Confirm a booking and move it from pending to active
     */
    public function confirmBooking(string $booking_id, float $final_price): void
    {
        // Find and remove from pending
        $pendingBooking = null;
        $this->pending_bookings = array_filter($this->pending_bookings, function ($booking) use ($booking_id, &$pendingBooking) {
            if ($booking['booking_id'] === $booking_id) {
                $pendingBooking = $booking;
                return false;
            }
            return true;
        });

        // Add to active bookings
        if ($pendingBooking) {
            $this->active_bookings[] = array_merge($pendingBooking, [
                'final_price' => $final_price,
                'confirmed_at' => now()->toISOString(),
            ]);

            $this->last_booked_at = now();
            $this->total_revenue += $final_price;
            $this->total_bookings++;

            // Update status if billboard is now occupied
            $this->updateStatusBasedOnBookings();
        }
    }

    /**
     * Update billboard status based on current bookings
     */
    private function updateStatusBasedOnBookings(): void
    {
        $now = now();
        $isCurrentlyOccupied = false;

        foreach ($this->active_bookings as $booking) {
            $start = Carbon::parse($booking['start_date']);
            $end = Carbon::parse($booking['end_date']);

            if ($now->greaterThanOrEqualTo($start) && $now->lessThanOrEqualTo($end)) {
                $isCurrentlyOccupied = true;
                break;
            }
        }

        $this->status = $isCurrentlyOccupied ? 'occupied' : 'available';
    }
}
