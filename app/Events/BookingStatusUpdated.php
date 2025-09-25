<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Billboard;
use App\States\BookingState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class BookingStatusUpdated extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    #[StateId(BookingState::class)]
    public string $booking_id;

    public function __construct(
        string $booking_id,
        public string $old_status,
        public string $new_status,
        public ?float $final_price = null,
        public ?string $reason = null,
        public ?array $notification_data = null,
    ) {
        $this->booking_id = $booking_id;
    }

    public function apply(BookingState $state): void
    {
        $state->status = $this->new_status;
        $state->last_status_change = now()->toISOString();

        if ($this->final_price !== null) {
            $state->final_price = $this->final_price;
        }

        // Update status history
        $history = $state->status_history ?? [];
        $history[] = [
            'from_status' => $this->old_status,
            'to_status' => $this->new_status,
            'changed_at' => now()->toISOString(),
            'reason' => $this->reason,
            'final_price' => $this->final_price,
        ];

        $state->status_history = $history;

        // Set confirmation/rejection timestamps
        if ($this->new_status === Booking::STATUS_CONFIRMED) {
            $state->confirmed_at = now()->toISOString();
        } elseif ($this->new_status === Booking::STATUS_REJECTED) {
            $state->rejected_at = now()->toISOString();
        }
    }

    public function handle(): void
    {
        $booking = Booking::find($this->booking_id);
        if ($booking) {
            $updateData = ['status' => $this->new_status];

            if ($this->final_price !== null) {
                $updateData['final_price'] = $this->final_price;
            }

            if ($this->new_status === Booking::STATUS_CONFIRMED) {
                $updateData['confirmed_at'] = now();
            } elseif ($this->new_status === Booking::STATUS_REJECTED) {
                $updateData['rejected_at'] = now();
                $updateData['rejection_reason'] = $this->reason;
            }

            $booking->update($updateData);

            // Update billboard status if booking is confirmed/cancelled
            if ($this->new_status === Booking::STATUS_CONFIRMED) {
                BillboardAvailabilityChanged::fire(
                    billboard_id: $booking->billboard_id,
                    old_status: Billboard::STATUS_AVAILABLE,
                    new_status: Billboard::STATUS_OCCUPIED,
                    booking_details: [
                        'booking_id' => $booking->id,
                        'client_name' => $booking->client->name,
                        'start_date' => $booking->start_date->toISOString(),
                        'end_date' => $booking->end_date->toISOString(),
                    ]
                );
            } elseif (in_array($this->new_status, [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED])) {
                BillboardAvailabilityChanged::fire(
                    billboard_id: $booking->billboard_id,
                    old_status: Billboard::STATUS_OCCUPIED,
                    new_status: Billboard::STATUS_AVAILABLE,
                    reason: "Booking {$this->new_status}"
                );
            }
        }
    }

    public function broadcastOn(): array
    {
        $booking = Booking::find($this->booking_id);
        $tenantId = $booking->tenant_id;

        return [
            new PrivateChannel("tenant.{$tenantId}.bookings"),
            new PrivateChannel("booking.{$this->booking_id}"),
            new PrivateChannel("client.{$booking->client_id}.bookings"),
            new PrivateChannel("tenant.{$tenantId}.dashboard"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.status.updated';
    }

    public function broadcastWith(): array
    {
        $booking = Booking::with(['billboard', 'client'])->find($this->booking_id);

        return [
            'booking_id' => $this->booking_id,
            'booking' => [
                'id' => $booking->id,
                'billboard_name' => $booking->billboard->name,
                'client_name' => $booking->client->name,
                'start_date' => $booking->start_date->toISOString(),
                'end_date' => $booking->end_date->toISOString(),
                'requested_price' => $booking->requested_price,
                'final_price' => $booking->final_price,
            ],
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'reason' => $this->reason,
            'timestamp' => now()->toISOString(),
            'notification' => $this->notification_data,
        ];
    }

    public function fired(): void
    {
        // Send appropriate notifications based on status
        match ($this->new_status) {
            Booking::STATUS_CONFIRMED => $this->sendConfirmationNotifications(),
            Booking::STATUS_REJECTED => $this->sendRejectionNotifications(),
            default => null,
        };
    }

    private function sendConfirmationNotifications(): void
    {
        BookingConfirmationSent::fire(
            booking_id: $this->booking_id,
            final_price: $this->final_price
        );
    }

    private function sendRejectionNotifications(): void
    {
        BookingRejectionSent::fire(
            booking_id: $this->booking_id,
            reason: $this->reason
        );
    }
}