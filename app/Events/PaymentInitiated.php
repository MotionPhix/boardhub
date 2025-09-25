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

class PaymentInitiated extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    #[StateId(PaymentState::class)]
    public int $payment_id;

    public function __construct(
        int $payment_id,
        public string $provider,
        public float $amount,
        public string $phone_number,
        public string $reference,
    ) {
        $this->payment_id = $payment_id;
    }

    public function apply(PaymentState $state): void
    {
        $state->provider = $this->provider;
        $state->amount = $this->amount;
        $state->phone_number = $this->phone_number;
        $state->reference = $this->reference;
        $state->status = Payment::STATUS_PENDING;
        $state->initiated_at = now()->toISOString();

        // Add to payment history
        $state->payment_history[] = [
            'status' => Payment::STATUS_PENDING,
            'timestamp' => now()->toISOString(),
            'provider' => $this->provider,
            'amount' => $this->amount,
        ];
    }

    public function handle(): void
    {
        $payment = Payment::find($this->payment_id);
        if (!$payment) return;

        // Log payment initiation
        activity()
            ->performedOn($payment)
            ->withProperties([
                'provider' => $this->provider,
                'amount' => $this->amount,
                'phone_number' => $this->phone_number,
                'reference' => $this->reference,
            ])
            ->log('Payment initiated');

        // Send SMS notification to customer (if applicable)
        $this->sendPaymentInitiatedNotification($payment);
    }

    public function broadcastOn(): array
    {
        $payment = Payment::find($this->payment_id);

        return [
            new PrivateChannel("tenant.{$payment->tenant_id}.payments"),
            new PrivateChannel("payment.{$this->payment_id}"),
            new PrivateChannel("booking.{$payment->booking_id}.payments"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.initiated';
    }

    public function broadcastWith(): array
    {
        $payment = Payment::find($this->payment_id);

        return [
            'payment_id' => $this->payment_id,
            'provider' => $this->provider,
            'amount' => $this->amount,
            'currency' => $payment->currency,
            'reference' => $this->reference,
            'status' => Payment::STATUS_PENDING,
            'timestamp' => now()->toISOString(),
            'booking_id' => $payment->booking_id,
        ];
    }

    private function sendPaymentInitiatedNotification(Payment $payment): void
    {
        \App\Events\RealTimeNotificationSent::dispatch(
            tenant_id: $payment->tenant_id,
            type: 'payment_initiated',
            title: 'Payment Initiated',
            message: "Payment of {$payment->formatted_amount} initiated via {$payment->provider_display_name}",
            data: [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'provider' => $payment->provider,
                'reference' => $payment->reference,
            ],
            priority: 'normal'
        );
    }
}