<?php

namespace App\States;

use Thunk\Verbs\State;

class PaymentState extends State
{
    public string $provider;
    public float $amount;
    public string $phone_number;
    public string $reference;
    public string $status = 'pending';
    public ?string $external_id = null;

    // Timestamps
    public ?string $initiated_at = null;
    public ?string $completed_at = null;
    public ?string $failed_at = null;

    // Processing metrics
    public ?int $processing_duration_seconds = null;
    public array $payment_history = [];
    public array $provider_responses = [];

    // Retry tracking
    public int $retry_count = 0;
    public array $retry_history = [];

    // Customer interaction
    public array $customer_notifications_sent = [];
    public ?string $customer_confirmation_method = null;
}