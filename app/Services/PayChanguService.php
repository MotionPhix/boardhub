<?php

namespace App\Services;

use App\Events\PaymentCompleted;
use App\Events\PaymentInitiated;
use App\Events\RegularPaymentFailed;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayChanguService
{
    private string $baseUrl;

    private string $secretKey;

    private string $publicKey;

    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('paychangu.base_url', 'https://api.paychangu.com');
        $this->secretKey = config('paychangu.secret_key');
        $this->publicKey = config('paychangu.public_key');
        $this->webhookSecret = config('paychangu.webhook_secret');
    }

    /**
     * Get supported payment providers for tenant
     */
    public function getSupportedProviders(Tenant $tenant): array
    {
        return [
            [
                'id' => 'card',
                'name' => 'Card Payment',
                'logo' => '/images/providers/card.png',
                'enabled' => true,
                'description' => 'Visa, Mastercard, and other major cards',
            ],
            [
                'id' => 'airtel_money',
                'name' => 'Airtel Money',
                'logo' => '/images/providers/airtel.png',
                'enabled' => true,
                'description' => 'Mobile money via Airtel Money',
            ],
            [
                'id' => 'tnm_mpamba',
                'name' => 'TNM Mpamba',
                'logo' => '/images/providers/tnm.png',
                'enabled' => true,
                'description' => 'Mobile money via TNM Mpamba',
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'logo' => '/images/providers/bank.png',
                'enabled' => true,
                'description' => 'Instant bank transfer',
            ],
        ];
    }

    /**
     * Process payment using PayChangu
     */
    public function processPayment(array $paymentData): array
    {
        try {
            // Create payment record
            $payment = $this->createPaymentRecord($paymentData);

            // Fire payment initiated event
            PaymentInitiated::dispatch(
                $payment->id,
                $paymentData['provider'],
                $paymentData['amount'],
                $paymentData['phone_number'] ?? null,
                $payment->reference
            );

            // Determine payment method and process accordingly
            $result = match ($paymentData['provider']) {
                'airtel_money', 'tnm_mpamba' => $this->processMobileMoneyPayment($payment, $paymentData),
                'card' => $this->processCardPayment($payment, $paymentData),
                'bank_transfer' => $this->processBankTransferPayment($payment, $paymentData),
                default => throw new \Exception('Unsupported payment provider: '.$paymentData['provider'])
            };

            return $result;

        } catch (\Exception $e) {
            Log::error('PayChangu payment processing failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
            ]);

            throw $e;
        }
    }

    /**
     * Process mobile money payment using PayChangu Direct Charge
     */
    private function processMobileMoneyPayment(Payment $payment, array $paymentData): array
    {
        $payload = [
            'tx_ref' => $payment->reference,
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'phone_number' => $paymentData['phone_number'],
            'provider' => $paymentData['provider'] === 'airtel_money' ? 'airtel' : 'mpamba',
            'callback_url' => route('api.webhooks.payments.paychangu'),
            'meta' => [
                'tenant_id' => $paymentData['tenant_id'],
                'booking_id' => $paymentData['booking_id'] ?? null,
                'client_id' => $paymentData['client_id'] ?? null,
                'payment_id' => $payment->id,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->secretKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/mobile-money/charge', $payload);

        if (! $response->successful()) {
            $this->handlePaymentFailure($payment, $response->json()['message'] ?? 'Mobile money payment failed');
            throw new \Exception('Mobile money payment failed: '.$response->body());
        }

        $responseData = $response->json();

        // Update payment with PayChangu transaction reference
        $payment->update([
            'external_id' => $responseData['data']['tx_ref'] ?? null,
            'provider_response' => $responseData,
            'status' => $this->mapPayChanguStatus($responseData['data']['status'] ?? 'pending'),
        ]);

        return [
            'payment_id' => $payment->uuid,
            'tx_ref' => $responseData['data']['tx_ref'],
            'status' => $responseData['data']['status'],
            'provider' => $paymentData['provider'],
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'message' => 'Mobile money payment initiated successfully',
        ];
    }

    /**
     * Process card payment using PayChangu Standard Checkout
     */
    private function processCardPayment(Payment $payment, array $paymentData): array
    {
        $payload = [
            'tx_ref' => $payment->reference,
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'email' => $paymentData['email'] ?? $payment->client?->email,
            'first_name' => $paymentData['first_name'] ?? $payment->client?->first_name ?? 'Customer',
            'last_name' => $paymentData['last_name'] ?? $payment->client?->last_name ?? '',
            'callback_url' => route('api.webhooks.payments.paychangu'),
            'return_url' => $paymentData['return_url'] ?? route('tenant.bookings.show', [
                'tenant' => $payment->tenant->uuid,
                'booking' => $payment->booking?->uuid ?? 'payment',
            ]),
            'customization' => [
                'title' => $paymentData['title'] ?? 'AdPro Billboard Payment',
                'description' => $paymentData['description'] ?? 'Payment for billboard booking',
            ],
            'meta' => [
                'tenant_id' => $paymentData['tenant_id'],
                'booking_id' => $paymentData['booking_id'] ?? null,
                'client_id' => $paymentData['client_id'] ?? null,
                'payment_id' => $payment->id,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->secretKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/payment', $payload);

        if (! $response->successful()) {
            $this->handlePaymentFailure($payment, $response->json()['message'] ?? 'Card payment failed');
            throw new \Exception('Card payment failed: '.$response->body());
        }

        $responseData = $response->json();

        // Update payment with PayChangu transaction reference
        $payment->update([
            'external_id' => $responseData['data']['data']['tx_ref'] ?? null,
            'provider_response' => $responseData,
            'status' => $this->mapPayChanguStatus($responseData['data']['data']['status'] ?? 'pending'),
        ]);

        return [
            'payment_id' => $payment->uuid,
            'tx_ref' => $responseData['data']['data']['tx_ref'],
            'checkout_url' => $responseData['data']['checkout_url'],
            'status' => $responseData['data']['data']['status'],
            'provider' => 'card',
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'message' => 'Card payment session created successfully',
        ];
    }

    /**
     * Process bank transfer payment using PayChangu
     */
    private function processBankTransferPayment(Payment $payment, array $paymentData): array
    {
        $payload = [
            'tx_ref' => $payment->reference,
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'email' => $paymentData['email'] ?? $payment->client?->email,
            'first_name' => $paymentData['first_name'] ?? $payment->client?->first_name ?? 'Customer',
            'last_name' => $paymentData['last_name'] ?? $payment->client?->last_name ?? '',
            'callback_url' => route('api.webhooks.payments.paychangu'),
            'return_url' => $paymentData['return_url'] ?? route('tenant.bookings.show', [
                'tenant' => $payment->tenant->uuid,
                'booking' => $payment->booking?->uuid ?? 'payment',
            ]),
            'customization' => [
                'title' => $paymentData['title'] ?? 'AdPro Billboard Payment',
                'description' => $paymentData['description'] ?? 'Payment for billboard booking',
            ],
            'meta' => [
                'tenant_id' => $paymentData['tenant_id'],
                'booking_id' => $paymentData['booking_id'] ?? null,
                'client_id' => $paymentData['client_id'] ?? null,
                'payment_id' => $payment->id,
                'payment_method' => 'bank_transfer',
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->secretKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/payment', $payload);

        if (! $response->successful()) {
            $this->handlePaymentFailure($payment, $response->json()['message'] ?? 'Bank transfer payment failed');
            throw new \Exception('Bank transfer payment failed: '.$response->body());
        }

        $responseData = $response->json();

        // Update payment with PayChangu transaction reference
        $payment->update([
            'external_id' => $responseData['data']['data']['tx_ref'] ?? null,
            'provider_response' => $responseData,
            'status' => $this->mapPayChanguStatus($responseData['data']['data']['status'] ?? 'pending'),
        ]);

        return [
            'payment_id' => $payment->uuid,
            'tx_ref' => $responseData['data']['data']['tx_ref'],
            'checkout_url' => $responseData['data']['checkout_url'],
            'status' => $responseData['data']['data']['status'],
            'provider' => 'bank_transfer',
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'message' => 'Bank transfer payment session created successfully',
        ];
    }

    /**
     * Check payment status with PayChangu
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        try {
            // Use external_id if available, otherwise use reference
            $txRef = $payment->external_id ?: $payment->reference;

            if (! $txRef) {
                throw new \Exception('No transaction reference available for payment status check');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl.'/verify-payment/'.$txRef);

            if (! $response->successful()) {
                throw new \Exception('Failed to check payment status: '.$response->body());
            }

            $responseData = $response->json();
            $status = $responseData['data']['status'] ?? 'unknown';

            // Update payment status if changed
            $mappedStatus = $this->mapPayChanguStatus($status);
            if ($payment->status !== $mappedStatus) {
                $payment->update([
                    'status' => $mappedStatus,
                    'provider_response' => $responseData,
                    'completed_at' => $status === 'success' ? now() : null,
                    'failed_at' => $status === 'failed' ? now() : null,
                    'failure_reason' => $status === 'failed' ? ($responseData['data']['logs'][0]['message'] ?? 'Payment failed') : null,
                ]);

                // Fire appropriate events
                if ($status === 'success') {
                    PaymentCompleted::dispatch(
                        $payment->id,
                        $payment->provider,
                        (float) $payment->amount,
                        $payment->external_id
                    );
                } elseif ($status === 'failed') {
                    RegularPaymentFailed::dispatch(
                        $payment->id,
                        $payment->provider,
                        (float) $payment->amount,
                        $responseData['data']['logs'][0]['message'] ?? 'Payment failed',
                        $payment->external_id
                    );
                }
            }

            return [
                'status' => $status,
                'tx_ref' => $responseData['data']['tx_ref'],
                'amount' => $responseData['data']['amount'],
                'currency' => $responseData['data']['currency'],
                'authorization' => $responseData['data']['authorization'] ?? null,
                'logs' => $responseData['data']['logs'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('PayChangu status check failed', [
                'payment_id' => $payment->id,
                'external_id' => $payment->external_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle PayChangu webhook callbacks
     */
    public function handlePaymentCallback(array $callbackData, string $provider): array
    {
        try {
            // Verify webhook signature
            if (! $this->verifyWebhookSignature($callbackData, request()->header('Signature'))) {
                Log::warning('Invalid PayChangu webhook signature', [
                    'provider' => $provider,
                    'data' => $callbackData,
                ]);

                return ['success' => false, 'message' => 'Invalid webhook signature'];
            }

            $txRef = $callbackData['reference'] ?? $callbackData['tx_ref'] ?? null;
            if (! $txRef) {
                Log::warning('PayChangu webhook missing transaction reference', $callbackData);

                return ['success' => false, 'message' => 'Missing transaction reference'];
            }

            // Find payment by external_id or reference
            $payment = Payment::where('external_id', $txRef)
                ->orWhere('reference', $txRef)
                ->first();

            if (! $payment) {
                Log::warning('PayChangu webhook: Payment not found', [
                    'tx_ref' => $txRef,
                    'callback_data' => $callbackData,
                ]);

                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Process the webhook based on event type
            $eventType = $callbackData['event_type'] ?? 'unknown';
            $status = $callbackData['status'] ?? 'unknown';

            $mappedStatus = $this->mapPayChanguStatus($status);

            // Update payment
            $payment->update([
                'status' => $mappedStatus,
                'external_id' => $txRef,
                'provider_response' => array_merge($payment->provider_response ?? [], $callbackData),
                'completed_at' => $status === 'success' ? now() : null,
                'failed_at' => in_array($status, ['failed', 'cancelled']) ? now() : null,
                'failure_reason' => $status === 'failed' ? ($callbackData['message'] ?? 'Payment failed') : null,
            ]);

            // Fire appropriate events
            if ($status === 'success') {
                PaymentCompleted::dispatch(
                    $payment->id,
                    $payment->provider,
                    (float) $payment->amount,
                    $txRef
                );
            } elseif (in_array($status, ['failed', 'cancelled'])) {
                RegularPaymentFailed::dispatch(
                    $payment->id,
                    $payment->provider,
                    (float) $payment->amount,
                    $callbackData['message'] ?? 'Payment failed',
                    $txRef
                );
            }

            Log::info('PayChangu webhook processed successfully', [
                'payment_id' => $payment->id,
                'tx_ref' => $txRef,
                'status' => $status,
                'event_type' => $eventType,
            ]);

            return ['success' => true, 'message' => 'Webhook processed successfully'];

        } catch (\Exception $e) {
            Log::error('PayChangu webhook processing failed', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData,
            ]);

            return ['success' => false, 'message' => 'Webhook processing failed'];
        }
    }

    /**
     * Get wallet balance from PayChangu
     */
    public function getWalletBalance(string $currency = 'MWK'): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl.'/wallet-balance', [
                'currency' => $currency,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Failed to get wallet balance: '.$response->body());
            }

            return $response->json()['data'];

        } catch (\Exception $e) {
            Log::error('PayChangu wallet balance check failed', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create payment record in database
     */
    private function createPaymentRecord(array $paymentData): Payment
    {
        return Payment::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $paymentData['tenant_id'],
            'booking_id' => $paymentData['booking_id'] ?? null,
            'client_id' => $paymentData['client_id'] ?? null,
            'provider' => $paymentData['provider'],
            'amount' => $paymentData['amount'],
            'currency' => 'MWK',
            'phone_number' => $paymentData['phone_number'] ?? null,
            'reference' => $paymentData['reference'] ?? 'ADPRO_'.strtoupper(uniqid()),
            'status' => Payment::STATUS_PENDING,
            'metadata' => $paymentData['metadata'] ?? [],
        ]);
    }

    /**
     * Map PayChangu status to internal status
     */
    private function mapPayChanguStatus(string $paychanguStatus): string
    {
        return match (strtolower($paychanguStatus)) {
            'success', 'successful', 'completed' => Payment::STATUS_COMPLETED,
            'pending', 'processing' => Payment::STATUS_PENDING,
            'failed', 'declined' => Payment::STATUS_FAILED,
            'cancelled' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_PENDING
        };
    }

    /**
     * Handle payment failure
     */
    private function handlePaymentFailure(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);

        RegularPaymentFailed::dispatch(
            $payment->id,
            $payment->provider,
            (float) $payment->amount,
            $reason,
            $payment->external_id
        );
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(array $callbackData, ?string $signature = null): bool
    {
        if (! $signature || ! $this->webhookSecret) {
            return false;
        }

        // Get the raw payload from the request
        $payload = request()->getContent();

        // If no raw payload, fall back to JSON encoding
        if (empty($payload)) {
            $payload = json_encode($callbackData);
        }

        $computedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
