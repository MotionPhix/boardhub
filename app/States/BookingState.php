<?php

namespace App\States;

use Carbon\Carbon;
use Thunk\Verbs\State;

class BookingState extends State
{
    public ?int $billboard_id = null;
    public ?int $client_id = null;
    public ?Carbon $start_date = null;
    public ?Carbon $end_date = null;
    public ?float $requested_price = null;
    public ?float $final_price = null;
    public ?string $campaign_details = null;
    public ?string $status = null; // requested, confirmed, rejected, cancelled, completed
    public ?Carbon $requested_at = null;
    public ?Carbon $confirmed_at = null;
    public ?Carbon $rejected_at = null;
    public ?string $rejection_reason = null;
    public array $price_negotiations = [];
    public array $status_history = [];

    /**
     * Add a price negotiation entry
     */
    public function addPriceNegotiation(float $offered_price, string $offered_by, ?string $notes = null): void
    {
        $this->price_negotiations[] = [
            'offered_price' => $offered_price,
            'offered_by' => $offered_by, // 'client' or 'owner'
            'notes' => $notes,
            'offered_at' => now()->toISOString(),
        ];
    }

    /**
     * Record status change in history
     */
    public function recordStatusChange(string $new_status, ?string $reason = null): void
    {
        $this->status_history[] = [
            'from_status' => $this->status,
            'to_status' => $new_status,
            'reason' => $reason,
            'changed_at' => now()->toISOString(),
        ];

        $this->status = $new_status;
    }

    /**
     * Calculate total booking duration in days
     */
    public function getDurationInDays(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if booking is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'confirmed' || !$this->start_date || !$this->end_date) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->start_date) &&
               $now->lessThanOrEqualTo($this->end_date);
    }
}
