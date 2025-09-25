<?php

namespace App\Events;

use App\States\BillboardState;
use App\States\BookingState;
use Carbon\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class BillboardBookingRequested extends Event
{
    #[StateId(BillboardState::class)]
    public int $billboard_id;

    #[StateId(BookingState::class)]
    public string $booking_id;

    public int $client_id;
    public Carbon $start_date;
    public Carbon $end_date;
    public float $requested_price;
    public ?string $campaign_details = null;

    public function validate(BillboardState $billboardState, BookingState $bookingState): bool
    {
        // Ensure billboard exists and is available
        $this->assert(
            $billboardState->status === 'available',
            'Billboard is not available for booking.'
        );

        // Ensure booking doesn't already exist
        $this->assert(
            $bookingState->status === null,
            'Booking request already exists.'
        );

        // Validate date range
        $this->assert(
            $this->start_date->isAfter(now()),
            'Start date must be in the future.'
        );

        $this->assert(
            $this->end_date->isAfter($this->start_date),
            'End date must be after start date.'
        );

        // Check for conflicting bookings (simplified check)
        $this->assert(
            !$billboardState->hasConflictingBooking($this->start_date, $this->end_date),
            'Billboard is already booked for the requested period.'
        );

        return true;
    }

    public function apply(BillboardState $billboardState, BookingState $bookingState): void
    {
        // Update booking state
        $bookingState->billboard_id = $this->billboard_id;
        $bookingState->client_id = $this->client_id;
        $bookingState->start_date = $this->start_date;
        $bookingState->end_date = $this->end_date;
        $bookingState->requested_price = $this->requested_price;
        $bookingState->campaign_details = $this->campaign_details;
        $bookingState->status = 'requested';
        $bookingState->requested_at = now();

        // Add booking to billboard's pending bookings
        $billboardState->addPendingBooking($this->booking_id, $this->start_date, $this->end_date);
    }

    public function handle(): void
    {
        // Create booking record in database for UI display
        \App\Models\Booking::create([
            'id' => $this->booking_id,
            'billboard_id' => $this->billboard_id,
            'client_id' => $this->client_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'requested_price' => $this->requested_price,
            'campaign_details' => $this->campaign_details,
            'status' => 'requested',
        ]);

        // TODO: Send notification to billboard owner
        // TODO: Send confirmation email to client
    }
}
