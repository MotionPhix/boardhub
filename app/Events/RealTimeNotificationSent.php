<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealTimeNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenant_id,
        public ?int $user_id = null,
        public string $type,
        public string $title,
        public string $message,
        public array $data = [],
        public string $priority = 'normal', // low, normal, high, urgent
        public ?string $action_url = null,
        public ?array $action_buttons = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("tenant.{$this->tenant_id}.notifications"),
        ];

        // Also broadcast to specific user if provided
        if ($this->user_id) {
            $channels[] = new PrivateChannel("user.{$this->user_id}.notifications");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => uniqid('notif_'),
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'priority' => $this->priority,
            'action_url' => $this->action_url,
            'action_buttons' => $this->action_buttons,
            'timestamp' => now()->toISOString(),
            'read' => false,
        ];
    }

    /**
     * Send booking-related notifications
     */
    public static function sendBookingNotification(
        int $tenant_id,
        string $type,
        array $booking_data,
        ?int $user_id = null
    ): void {
        $notifications = match ($type) {
            'booking_requested' => [
                'title' => 'New Booking Request',
                'message' => "New booking request for {$booking_data['billboard_name']} from {$booking_data['client_name']}",
                'priority' => 'high',
                'action_url' => route('tenant.bookings.show', ['tenant' => $tenant_id, 'booking' => $booking_data['booking_id']]),
                'action_buttons' => [
                    ['label' => 'Review', 'action' => 'review', 'style' => 'primary'],
                    ['label' => 'Quick Approve', 'action' => 'approve', 'style' => 'success'],
                ],
            ],
            'booking_confirmed' => [
                'title' => 'Booking Confirmed',
                'message' => "Your booking for {$booking_data['billboard_name']} has been confirmed!",
                'priority' => 'normal',
                'action_url' => route('tenant.bookings.show', ['tenant' => $tenant_id, 'booking' => $booking_data['booking_id']]),
            ],
            'booking_rejected' => [
                'title' => 'Booking Rejected',
                'message' => "Your booking request for {$booking_data['billboard_name']} was rejected",
                'priority' => 'normal',
                'action_url' => route('tenant.billboards.index', ['tenant' => $tenant_id]),
                'action_buttons' => [
                    ['label' => 'Browse Other Billboards', 'action' => 'browse', 'style' => 'primary'],
                ],
            ],
            'payment_required' => [
                'title' => 'Payment Required',
                'message' => "Payment is required for your booking of {$booking_data['billboard_name']}",
                'priority' => 'urgent',
                'action_url' => route('tenant.bookings.payment', ['tenant' => $tenant_id, 'booking' => $booking_data['booking_id']]),
                'action_buttons' => [
                    ['label' => 'Pay Now', 'action' => 'pay', 'style' => 'success'],
                ],
            ],
            default => null,
        };

        if ($notifications) {
            static::dispatch(
                tenant_id: $tenant_id,
                user_id: $user_id,
                type: $type,
                title: $notifications['title'],
                message: $notifications['message'],
                data: $booking_data,
                priority: $notifications['priority'],
                action_url: $notifications['action_url'] ?? null,
                action_buttons: $notifications['action_buttons'] ?? null,
            );
        }
    }

    /**
     * Send system notifications
     */
    public static function sendSystemNotification(
        int $tenant_id,
        string $type,
        array $data = [],
        ?int $user_id = null
    ): void {
        $notifications = match ($type) {
            'limit_warning' => [
                'title' => 'Approaching Subscription Limit',
                'message' => "You're approaching your {$data['limit_type']} limit ({$data['current']}/{$data['limit']})",
                'priority' => 'high',
                'action_url' => route('tenant.subscription.upgrade', ['tenant' => $tenant_id]),
                'action_buttons' => [
                    ['label' => 'Upgrade Plan', 'action' => 'upgrade', 'style' => 'primary'],
                ],
            ],
            'trial_ending' => [
                'title' => 'Trial Ending Soon',
                'message' => "Your trial ends in {$data['days_remaining']} days",
                'priority' => 'urgent',
                'action_url' => route('tenant.subscription.plans', ['tenant' => $tenant_id]),
                'action_buttons' => [
                    ['label' => 'Choose Plan', 'action' => 'subscribe', 'style' => 'success'],
                ],
            ],
            'feature_unlocked' => [
                'title' => 'New Features Unlocked!',
                'message' => "You now have access to: " . implode(', ', $data['features']),
                'priority' => 'normal',
                'action_url' => route('tenant.dashboard', ['tenant' => $tenant_id]),
            ],
            default => null,
        };

        if ($notifications) {
            static::dispatch(
                tenant_id: $tenant_id,
                user_id: $user_id,
                type: $type,
                title: $notifications['title'],
                message: $notifications['message'],
                data: $data,
                priority: $notifications['priority'],
                action_url: $notifications['action_url'] ?? null,
                action_buttons: $notifications['action_buttons'] ?? null,
            );
        }
    }
}