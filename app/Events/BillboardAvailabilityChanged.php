<?php

namespace App\Events;

use App\Models\Billboard;
use App\States\BillboardState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class BillboardAvailabilityChanged extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    #[StateId(BillboardState::class)]
    public int $billboard_id;

    public function __construct(
        int $billboard_id,
        public string $old_status,
        public string $new_status,
        public ?array $booking_details = null,
        public ?string $reason = null,
    ) {
        $this->billboard_id = $billboard_id;
    }

    public function apply(BillboardState $state): void
    {
        $state->status = $this->new_status;
        $state->last_status_change = now()->toISOString();

        if ($this->booking_details) {
            $state->current_booking = $this->booking_details;
        }

        // Update availability history
        $history = $state->availability_history ?? [];
        $history[] = [
            'from_status' => $this->old_status,
            'to_status' => $this->new_status,
            'changed_at' => now()->toISOString(),
            'reason' => $this->reason,
        ];

        // Keep only last 50 changes
        $state->availability_history = array_slice($history, -50);
    }

    public function handle(): void
    {
        $billboard = Billboard::find($this->billboard_id);
        if ($billboard) {
            $billboard->update(['status' => $this->new_status]);

            // Log the change for analytics
            activity()
                ->performedOn($billboard)
                ->withProperties([
                    'old_status' => $this->old_status,
                    'new_status' => $this->new_status,
                    'reason' => $this->reason,
                ])
                ->log('Billboard availability changed');
        }
    }

    public function broadcastOn(): array
    {
        $billboard = Billboard::find($this->billboard_id);
        $tenantId = $billboard->tenant_id;

        return [
            new PrivateChannel("tenant.{$tenantId}.billboards"),
            new Channel("billboard.{$this->billboard_id}"),
            new PrivateChannel("tenant.{$tenantId}.dashboard"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'billboard.availability.changed';
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
                'size' => $billboard->size,
                'price' => $billboard->price,
            ],
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'reason' => $this->reason,
            'timestamp' => now()->toISOString(),
            'booking_details' => $this->booking_details,
        ];
    }
}