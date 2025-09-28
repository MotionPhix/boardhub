<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function paychangu(Request $request): Response
    {
        // Verify webhook signature
        if (!$this->verifyPayChanguSignature($request)) {
            \Log::warning('PayChangu webhook signature verification failed', [
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $eventType = $payload['event'] ?? null;

        \Log::info('PayChangu webhook received', [
            'event' => $eventType,
            'data' => $payload,
        ]);

        try {
            // Use HireThunk Verbs events for event sourcing
            switch ($eventType) {
                case 'payment.successful':
                    \App\Events\PaymentWasSuccessful::fire(
                        payment_data: $payload['data']
                    );
                    break;

                case 'payment.failed':
                    \App\Events\PaymentFailed::fire(
                        payment_data: $payload['data']
                    );
                    break;

                case 'subscription.cancelled':
                    \App\Events\SubscriptionWasCancelled::fire(
                        subscription_data: $payload['data']
                    );
                    break;

                case 'subscription.renewed':
                    \App\Events\SubscriptionWasRenewed::fire(
                        subscription_data: $payload['data']
                    );
                    break;

                case 'subscription.expired':
                    \App\Events\SubscriptionExpired::fire(
                        subscription_data: $payload['data']
                    );
                    break;

                default:
                    \Log::info('Unhandled PayChangu webhook event', [
                        'event' => $eventType,
                        'data' => $payload,
                    ]);
                    break;
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::error('PayChangu webhook processing failed', [
                'event' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response('Internal Server Error', 500);
        }
    }

    private function verifyPayChanguSignature(Request $request): bool
    {
        $signature = $request->header('X-PayChangu-Signature');
        $webhookSecret = config('services.paychangu.webhook_secret');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
