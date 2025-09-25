<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use App\Events\PaymentInitiated;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileMoneyService
{
    private const AIRTEL_MONEY_URL = 'https://openapi.airtel.africa';
    private const TNM_MPAMBA_URL = 'https://api.tnm.co.mw/mpamba';

    /**
     * Process payment using mobile money
     */
    public function processPayment(array $paymentData): array
    {
        $provider = $paymentData['provider']; // 'airtel_money' or 'tnm_mpamba'
        $amount = $paymentData['amount'];
        $phoneNumber = $paymentData['phone_number'];
        $reference = $paymentData['reference'] ?? 'ADPRO_' . strtoupper(Str::random(8));

        // Validate phone number format
        $validatedPhone = $this->validatePhoneNumber($phoneNumber, $provider);
        if (!$validatedPhone) {
            throw new Exception("Invalid phone number format for {$provider}");
        }

        // Create payment record
        $payment = Payment::create([
            'tenant_id' => $paymentData['tenant_id'],
            'booking_id' => $paymentData['booking_id'] ?? null,
            'client_id' => $paymentData['client_id'] ?? null,
            'provider' => $provider,
            'amount' => $amount,
            'currency' => 'MWK',
            'phone_number' => $validatedPhone,
            'reference' => $reference,
            'status' => 'pending',
            'external_id' => null,
            'metadata' => [
                'initiated_at' => now()->toISOString(),
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
            ],
        ]);

        // Fire payment initiated event
        PaymentInitiated::fire(
            payment_id: $payment->id,
            provider: $provider,
            amount: $amount,
            phone_number: $validatedPhone,
            reference: $reference
        );

        try {
            $result = match ($provider) {
                'airtel_money' => $this->processAirtelMoneyPayment($payment),
                'tnm_mpamba' => $this->processTnmMpambaPayment($payment),
                default => throw new Exception("Unsupported payment provider: {$provider}")
            };

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'reference' => $reference,
                'external_id' => $result['transaction_id'] ?? null,
                'status' => 'pending',
                'message' => 'Payment initiated successfully. Please complete on your phone.',
                'instructions' => $this->getPaymentInstructions($provider, $validatedPhone, $amount),
            ];

        } catch (Exception $e) {
            Log::error('Mobile money payment failed', [
                'payment_id' => $payment->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            PaymentFailed::fire(
                payment_id: $payment->id,
                provider: $provider,
                reason: $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Process Airtel Money payment
     */
    private function processAirtelMoneyPayment(Payment $payment): array
    {
        $accessToken = $this->getAirtelAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'X-Country' => 'MW',
            'X-Currency' => 'MWK',
        ])->post($this->getAirtelApiUrl('/merchant/v1/payments/'), [
            'reference' => $payment->reference,
            'subscriber' => [
                'country' => 'MW',
                'currency' => 'MWK',
                'msisdn' => $payment->phone_number,
            ],
            'transaction' => [
                'amount' => $payment->amount,
                'country' => 'MW',
                'currency' => 'MWK',
                'id' => $payment->reference,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception('Airtel Money API error: ' . $response->body());
        }

        $responseData = $response->json();

        if (!isset($responseData['transaction']['id'])) {
            throw new Exception('Invalid response from Airtel Money API');
        }

        $payment->update([
            'external_id' => $responseData['transaction']['id'],
            'provider_response' => $responseData,
            'status' => 'processing',
        ]);

        return [
            'transaction_id' => $responseData['transaction']['id'],
            'status' => $responseData['transaction']['status'] ?? 'pending',
        ];
    }

    /**
     * Process TNM Mpamba payment
     */
    private function processTnmMpambaPayment(Payment $payment): array
    {
        $accessToken = $this->getTnmAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->getTnmApiUrl('/payments/request'), [
            'amount' => $payment->amount,
            'currency' => 'MWK',
            'externalId' => $payment->reference,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $payment->phone_number,
            ],
            'payerMessage' => 'AdPro Billboard Booking Payment',
            'payeeNote' => 'Billboard booking payment - ' . $payment->reference,
        ]);

        if (!$response->successful()) {
            throw new Exception('TNM Mpamba API error: ' . $response->body());
        }

        $responseData = $response->json();

        if (!isset($responseData['referenceId'])) {
            throw new Exception('Invalid response from TNM Mpamba API');
        }

        $payment->update([
            'external_id' => $responseData['referenceId'],
            'provider_response' => $responseData,
            'status' => 'processing',
        ]);

        return [
            'transaction_id' => $responseData['referenceId'],
            'status' => 'pending',
        ];
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        if (!$payment->external_id) {
            return ['status' => 'pending', 'message' => 'Payment not yet processed'];
        }

        try {
            $result = match ($payment->provider) {
                'airtel_money' => $this->checkAirtelPaymentStatus($payment),
                'tnm_mpamba' => $this->checkTnmPaymentStatus($payment),
                default => throw new Exception("Unsupported payment provider: {$payment->provider}")
            };

            // Update payment status based on provider response
            $this->updatePaymentStatus($payment, $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment webhook/callback
     */
    public function handlePaymentCallback(array $callbackData, string $provider): array
    {
        Log::info('Payment callback received', [
            'provider' => $provider,
            'data' => $callbackData,
        ]);

        try {
            $result = match ($provider) {
                'airtel_money' => $this->handleAirtelCallback($callbackData),
                'tnm_mpamba' => $this->handleTnmCallback($callbackData),
                default => throw new Exception("Unsupported callback provider: {$provider}")
            };

            return $result;

        } catch (Exception $e) {
            Log::error('Payment callback handling failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'callback_data' => $callbackData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get supported mobile money providers for tenant
     */
    public function getSupportedProviders(Tenant $tenant): array
    {
        $providers = [
            'airtel_money' => [
                'name' => 'Airtel Money',
                'code' => 'airtel_money',
                'logo' => '/images/providers/airtel-money.png',
                'phone_format' => '265 99X XXX XXX',
                'prefixes' => ['99', '88'],
                'min_amount' => 100,
                'max_amount' => 1000000,
                'fee_percentage' => 1.5,
                'processing_time' => 'Instant',
                'available' => true,
            ],
            'tnm_mpamba' => [
                'name' => 'TNM Mpamba',
                'code' => 'tnm_mpamba',
                'logo' => '/images/providers/tnm-mpamba.png',
                'phone_format' => '265 77X XXX XXX',
                'prefixes' => ['77'],
                'min_amount' => 100,
                'max_amount' => 500000,
                'fee_percentage' => 2.0,
                'processing_time' => 'Instant',
                'available' => true,
            ],
        ];

        // Filter based on tenant's enabled payment methods
        $enabledProviders = $tenant->payment_settings['enabled_providers'] ?? array_keys($providers);

        return array_intersect_key($providers, array_flip($enabledProviders));
    }

    /**
     * Generate payment instructions for users
     */
    private function getPaymentInstructions(string $provider, string $phoneNumber, float $amount): array
    {
        $instructions = match ($provider) {
            'airtel_money' => [
                'title' => 'Complete payment with Airtel Money',
                'steps' => [
                    '1. Check your phone for an Airtel Money prompt',
                    '2. Enter your Airtel Money PIN when prompted',
                    '3. Confirm the payment of MWK ' . number_format($amount),
                    '4. Wait for confirmation SMS',
                ],
                'alternative' => [
                    'Dial *115# and follow the prompts to complete payment',
                    'Use reference: ' . request()->get('reference', 'N/A'),
                ],
                'timeout' => '5 minutes',
            ],
            'tnm_mpamba' => [
                'title' => 'Complete payment with TNM Mpamba',
                'steps' => [
                    '1. Check your phone for an Mpamba notification',
                    '2. Enter your Mpamba PIN when prompted',
                    '3. Confirm the payment of MWK ' . number_format($amount),
                    '4. Wait for confirmation SMS',
                ],
                'alternative' => [
                    'Dial *444# and follow the prompts to complete payment',
                    'Use reference: ' . request()->get('reference', 'N/A'),
                ],
                'timeout' => '5 minutes',
            ],
            default => [
                'title' => 'Complete your payment',
                'steps' => ['Follow the instructions on your phone'],
                'timeout' => '5 minutes',
            ],
        };

        return $instructions;
    }

    /**
     * Validate phone number format for specific provider
     */
    private function validatePhoneNumber(string $phoneNumber, string $provider): ?string
    {
        // Remove spaces, dashes, and plus signs
        $clean = preg_replace('/[\s\-\+]/', '', $phoneNumber);

        // Ensure it starts with country code
        if (!str_starts_with($clean, '265')) {
            if (str_starts_with($clean, '0')) {
                $clean = '265' . substr($clean, 1);
            } else {
                $clean = '265' . $clean;
            }
        }

        // Validate format based on provider
        $validFormats = [
            'airtel_money' => ['/^26599\d{7}$/', '/^26588\d{7}$/'],
            'tnm_mpamba' => ['/^26577\d{7}$/'],
        ];

        $patterns = $validFormats[$provider] ?? [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return $clean;
            }
        }

        return null;
    }

    // Helper methods for API communication
    private function getAirtelAccessToken(): string
    {
        $credentials = config('services.airtel_money');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->getAirtelApiUrl('/auth/oauth2/token'), [
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to get Airtel access token');
        }

        return $response->json()['access_token'];
    }

    private function getTnmAccessToken(): string
    {
        $credentials = config('services.tnm_mpamba');

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($credentials['api_key'] . ':' . $credentials['api_secret']),
        ])->post($this->getTnmApiUrl('/token'), [
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to get TNM access token');
        }

        return $response->json()['access_token'];
    }

    private function getAirtelApiUrl(string $endpoint): string
    {
        return self::AIRTEL_MONEY_URL . $endpoint;
    }

    private function getTnmApiUrl(string $endpoint): string
    {
        return self::TNM_MPAMBA_URL . $endpoint;
    }

    private function checkAirtelPaymentStatus(Payment $payment): array
    {
        $accessToken = $this->getAirtelAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Country' => 'MW',
            'X-Currency' => 'MWK',
        ])->get($this->getAirtelApiUrl("/standard/v1/payments/{$payment->external_id}"));

        if (!$response->successful()) {
            throw new Exception('Failed to check Airtel payment status');
        }

        $data = $response->json();

        return [
            'status' => $this->mapAirtelStatus($data['transaction']['status'] ?? 'unknown'),
            'provider_status' => $data['transaction']['status'] ?? 'unknown',
            'provider_data' => $data,
        ];
    }

    private function checkTnmPaymentStatus(Payment $payment): array
    {
        $accessToken = $this->getTnmAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get($this->getTnmApiUrl("/payments/{$payment->external_id}"));

        if (!$response->successful()) {
            throw new Exception('Failed to check TNM payment status');
        }

        $data = $response->json();

        return [
            'status' => $this->mapTnmStatus($data['status'] ?? 'unknown'),
            'provider_status' => $data['status'] ?? 'unknown',
            'provider_data' => $data,
        ];
    }

    private function updatePaymentStatus(Payment $payment, array $statusData): void
    {
        $newStatus = $statusData['status'];
        $oldStatus = $payment->status;

        if ($oldStatus !== $newStatus) {
            $payment->update([
                'status' => $newStatus,
                'provider_response' => array_merge($payment->provider_response ?? [], $statusData['provider_data'] ?? []),
                'status_checked_at' => now(),
                $newStatus === 'completed' ? 'completed_at' : 'updated_at' => now(),
            ]);

            if ($newStatus === 'completed') {
                PaymentCompleted::fire(
                    payment_id: $payment->id,
                    provider: $payment->provider,
                    amount: $payment->amount,
                    external_id: $payment->external_id
                );

                // Complete associated booking if exists
                if ($payment->booking) {
                    $this->completeBookingPayment($payment->booking);
                }
            } elseif ($newStatus === 'failed') {
                PaymentFailed::fire(
                    payment_id: $payment->id,
                    provider: $payment->provider,
                    reason: $statusData['failure_reason'] ?? 'Payment failed'
                );
            }
        }
    }

    private function completeBookingPayment(Booking $booking): void
    {
        $booking->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Fire booking payment completed event
        \App\Events\BookingPaymentCompleted::fire(
            booking_id: $booking->id,
            amount: $booking->final_price ?? $booking->requested_price
        );
    }

    // Status mapping methods
    private function mapAirtelStatus(string $airtelStatus): string
    {
        return match (strtolower($airtelStatus)) {
            'success', 'successful' => 'completed',
            'failed', 'failure' => 'failed',
            'pending', 'processing' => 'processing',
            default => 'pending',
        };
    }

    private function mapTnmStatus(string $tnmStatus): string
    {
        return match (strtolower($tnmStatus)) {
            'successful', 'completed' => 'completed',
            'failed', 'rejected' => 'failed',
            'pending', 'processing' => 'processing',
            default => 'pending',
        };
    }

    // Callback handlers
    private function handleAirtelCallback(array $data): array
    {
        // Handle Airtel Money callback
        // Implementation would depend on Airtel's callback format
        return ['success' => true];
    }

    private function handleTnmCallback(array $data): array
    {
        // Handle TNM Mpamba callback
        // Implementation would depend on TNM's callback format
        return ['success' => true];
    }
}