<?php

namespace App\Events;

use App\Models\Payment;
use App\States\PaymentState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class PaymentCompleted extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    #[StateId(PaymentState::class)]
    public int $payment_id;

    public function __construct(
        int $payment_id,
        public string $provider,
        public float $amount,
        public ?string $external_id = null,
    ) {
        $this->payment_id = $payment_id;
    }

    public function apply(PaymentState $state): void
    {
        $state->status = Payment::STATUS_COMPLETED;
        $state->completed_at = now()->toISOString();
        $state->external_id = $this->external_id;

        // Update payment history
        $state->payment_history[] = [
            'status' => Payment::STATUS_COMPLETED,
            'timestamp' => now()->toISOString(),
            'external_id' => $this->external_id,
        ];

        // Calculate processing duration
        if ($state->initiated_at) {
            $initiated = \Carbon\Carbon::parse($state->initiated_at);
            $state->processing_duration_seconds = $initiated->diffInSeconds(now());
        }
    }

    public function handle(): void
    {
        $payment = Payment::find($this->payment_id);
        if (!$payment) return;

        // Log successful payment
        activity()
            ->performedOn($payment)
            ->withProperties([
                'provider' => $this->provider,
                'amount' => $this->amount,
                'external_id' => $this->external_id,
                'duration_seconds' => $payment->getDuration(),
            ])
            ->log('Payment completed successfully');

        // Update tenant revenue statistics
        $this->updateTenantRevenue($payment);

        // Send confirmation notifications
        $this->sendPaymentCompletedNotifications($payment);

        // Complete associated booking if exists
        if ($payment->booking) {
            \App\Events\BookingPaymentCompleted::fire(
                booking_id: $payment->booking_id,
                amount: $this->amount
            );
        }
    }

    public function broadcastOn(): array
    {
        $payment = Payment::find($this->payment_id);

        return [
            new PrivateChannel("tenant.{$payment->tenant_id}.payments"),
            new PrivateChannel("payment.{$this->payment_id}"),
            new PrivateChannel("booking.{$payment->booking_id}.payments"),
            new PrivateChannel("tenant.{$payment->tenant_id}.dashboard"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.completed';
    }

    public function broadcastWith(): array
    {
        $payment = Payment::find($this->payment_id);

        return [
            'payment_id' => $this->payment_id,
            'provider' => $this->provider,
            'amount' => $this->amount,
            'currency' => $payment->currency,
            'external_id' => $this->external_id,
            'reference' => $payment->reference,
            'status' => Payment::STATUS_COMPLETED,
            'timestamp' => now()->toISOString(),
            'booking_id' => $payment->booking_id,
            'duration_seconds' => $payment->getDuration(),
        ];
    }

    private function updateTenantRevenue(Payment $payment): void
    {
        $tenant = $payment->tenant;

        $tenant->increment('total_revenue', $this->amount);

        // Update monthly revenue
        $monthlyRevenue = $tenant->payments()
            ->completed()
            ->thisMonth()
            ->sum('amount');

        $tenant->update(['monthly_revenue' => $monthlyRevenue]);

        // Fire tenant usage tracking event
        \App\Events\TenantUsageTracked::fire(
            tenant_id: $tenant->id,
            action: 'revenue_generated',
            metadata: [
                'amount' => $this->amount,
                'payment_provider' => $this->provider,
                'booking_id' => $payment->booking_id,
            ]
        );
    }

    private function sendPaymentCompletedNotifications(Payment $payment): void
    {
        // Real-time notification
        \App\Events\RealTimeNotificationSent::dispatch(
            tenant_id: $payment->tenant_id,
            type: 'payment_completed',
            title: 'Payment Successful! 🎉',
            message: "Payment of {$payment->formatted_amount} completed successfully",
            data: [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'provider' => $payment->provider,
                'reference' => $payment->reference,
                'external_id' => $this->external_id,
            ],
            priority: 'normal',
            action_url' => $payment->booking ?
                route('tenant.bookings.show', ['tenant' => $payment->tenant->uuid, 'booking' => $payment->booking->uuid]) :
                null
        );

        // SMS confirmation to customer (would integrate with SMS service)
        $this->sendSMSConfirmation($payment);

        // Email receipt (would integrate with email service)
        $this->sendEmailReceipt($payment);
    }

    private function sendSMSConfirmation(Payment $payment): void
    {
        // SMS confirmation logic would go here
        // Integration with SMS gateway like Africa's Talking, etc.
        Log::info('SMS confirmation sent', [
            'payment_id' => $payment->id,
            'phone' => $payment->phone_number,
            'amount' => $payment->amount,
        ]);
    }

    private function sendEmailReceipt(Payment $payment): void
    {
        // Email receipt logic would go here
        // Integration with email service
        if ($payment->client && $payment->client->email) {
            Log::info('Email receipt sent', [
                'payment_id' => $payment->id,
                'email' => $payment->client->email,
                'amount' => $payment->amount,
            ]);
        }
    }
}