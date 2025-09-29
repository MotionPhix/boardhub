<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function paychangu(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifyPayChanguSignature($request)) {
                Log::warning('PayChangu webhook signature verification failed', [
                    'headers' => $request->headers->all(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Log the verified webhook
            Log::info('PayChangu webhook received and verified', [
                'payload' => $request->all(),
            ]);

            $payload = $request->all();
            $eventType = $payload['event_type'] ?? null;

            // Handle different event types
            switch ($eventType) {
                case 'api.charge.payment':
                case 'checkout.payment':
                    return $this->handlePaymentWebhook($payload);
                default:
                    Log::info('PayChangu webhook event type not handled', [
                        'event_type' => $eventType,
                        'payload' => $payload
                    ]);
                    return response()->json(['status' => 'received']);
            }

        } catch (\Exception $e) {
            Log::error('PayChangu webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function paymentWebhook(Request $request, string $provider): JsonResponse
    {
        // Log the incoming webhook
        Log::info("Payment webhook received from {$provider}", [
            'provider' => $provider,
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // TODO: Implement provider-specific webhook handling
        switch ($provider) {
            case 'paychangu':
                return $this->handlePayChanguWebhook($request);
            case 'airtel':
                return $this->handleAirtelWebhook($request);
            case 'tnm':
                return $this->handleTnmWebhook($request);
            default:
                return response()->json(['error' => 'Unknown provider'], 400);
        }
    }

    public function systemAlert(Request $request): JsonResponse
    {
        // Log system alert
        Log::warning('System alert webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // TODO: Implement system alert handling
        // - Process monitoring alerts
        // - Trigger notifications
        // - Update system status

        return response()->json(['status' => 'received']);
    }

    private function verifyPayChanguSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('Signature');
        $webhookSecret = config('services.paychangu.webhook_secret');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }

    private function handlePaymentWebhook(array $payload): JsonResponse
    {
        try {
            $txRef = $payload['tx_ref'] ?? $payload['reference'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$txRef) {
                Log::warning('PayChangu webhook missing transaction reference', $payload);
                return response()->json(['error' => 'Missing transaction reference'], 400);
            }

            // Only process successful payments
            if ($status === 'success') {
                $this->processSuccessfulPayment($payload);
            } else {
                Log::info('PayChangu webhook for non-successful payment', [
                    'tx_ref' => $txRef,
                    'status' => $status,
                ]);
            }

            return response()->json(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error('PayChangu payment webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }

    private function processSuccessfulPayment(array $payload): void
    {
        $txRef = $payload['tx_ref'] ?? $payload['reference'];
        $metadata = $payload['meta'] ?? [];

        Log::info('Processing successful PayChangu payment', [
            'tx_ref' => $txRef,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
        ]);

        // Check if this is for organization creation
        if (isset($metadata['pending_organization'])) {
            $this->createOrganizationFromWebhook($payload);
        }

        // Additional payment processing logic can be added here
    }

    private function createOrganizationFromWebhook(array $payload): void
    {
        // This is a backup method in case the callback URL doesn't work
        // The main organization creation should happen in CheckoutController::handleCallback

        Log::info('Organization creation webhook received', [
            'tx_ref' => $payload['tx_ref'] ?? $payload['reference'],
        ]);

        // In most cases, the organization should already be created via callback
        // This webhook serves as a backup and verification
    }

    private function handlePayChanguWebhook(Request $request): JsonResponse
    {
        // This method is deprecated - use paychangu() instead
        return $this->paychangu($request);
    }

    private function handleAirtelWebhook(Request $request): JsonResponse
    {
        // TODO: Implement Airtel Money webhook logic
        return response()->json(['status' => 'processed']);
    }

    private function handleTnmWebhook(Request $request): JsonResponse
    {
        // TODO: Implement TNM Mpamba webhook logic
        return response()->json(['status' => 'processed']);
    }
}