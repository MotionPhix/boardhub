<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegularPaymentFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $payment_id;

    public function __construct(
        int $payment_id,
        public string $provider,
        public float $amount,
        public string $failure_reason,
        public ?string $external_id = null,
    ) {
        $this->payment_id = $payment_id;
    }

    public function handle(): void
    {
        $payment = Payment::find($this->payment_id);
        if (! $payment) {
            return;
        }

        // Log payment failure
        activity()
            ->performedOn($payment)
            ->withProperties([
                'provider' => $this->provider,
                'amount' => $this->amount,
                'failure_reason' => $this->failure_reason,
                'external_id' => $this->external_id,
            ])
            ->log('Payment failed');

        // Send failure notifications
        $this->sendPaymentFailedNotifications($payment);
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
        return 'payment.failed';
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
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => $this->failure_reason,
            'timestamp' => now()->toISOString(),
            'booking_id' => $payment->booking_id,
        ];
    }

    private function sendPaymentFailedNotifications(Payment $payment): void
    {
        // Real-time notification
        \App\Events\RealTimeNotificationSent::dispatch(
            tenant_id: $payment->tenant_id,
            type: 'payment_failed',
            title: 'Payment Failed ❌',
            message: "Payment of {$payment->formatted_amount} failed: {$this->failure_reason}",
            data: [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'provider' => $payment->provider,
                'reference' => $payment->reference,
                'external_id' => $this->external_id,
                'failure_reason' => $this->failure_reason,
            ],
            priority: 'high',
            action_url: $payment->booking ?
                route('tenant.bookings.show', ['tenant' => $payment->tenant->uuid, 'booking' => $payment->booking->uuid]) :
                null
        );

        // Log failure for customer support follow-up
        Log::warning('Payment failure notification sent', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'provider' => $payment->provider,
            'failure_reason' => $this->failure_reason,
        ]);
    }
}
