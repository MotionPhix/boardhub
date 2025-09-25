<?php

namespace Tests\Feature;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\PaymentInitiated;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PayChanguService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayChanguIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected User $user;
    protected Client $client;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Billboard Company',
            'slug' => 'test-billboard',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->booking = Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
        ]);

        // Set tenant context
        app()->instance('tenant', $this->tenant);
    }

    public function test_can_get_supported_payment_providers(): void
    {
        $response = $this->getJson("/api/t/{$this->tenant->uuid}/payments/providers");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'providers' => [
                        [
                            'id' => 'card',
                            'name' => 'Card Payment',
                            'enabled' => true,
                        ],
                        [
                            'id' => 'airtel_money',
                            'name' => 'Airtel Money',
                            'enabled' => true,
                        ],
                        [
                            'id' => 'tnm_mpamba',
                            'name' => 'TNM Mpamba',
                            'enabled' => true,
                        ],
                        [
                            'id' => 'bank_transfer',
                            'name' => 'Bank Transfer',
                            'enabled' => true,
                        ],
                    ],
                    'default_currency' => 'MWK',
                    'supported_currencies' => ['MWK', 'USD'],
                ],
            ]);
    }

    public function test_can_process_mobile_money_payment(): void
    {
        Event::fake([PaymentInitiated::class]);

        // Mock PayChangu API response
        Http::fake([
            'api.paychangu.com/mobile-money/charge' => Http::response([
                'status' => 'success',
                'data' => [
                    'tx_ref' => 'PAYCHANGU_12345',
                    'status' => 'pending',
                    'amount' => 5000,
                    'currency' => 'MWK',
                ],
            ], 200),
        ]);

        $paymentData = [
            'provider' => 'airtel_money',
            'amount' => 5000,
            'phone_number' => '0991234567',
            'booking_id' => $this->booking->id,
            'client_id' => $this->client->id,
        ];

        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", $paymentData);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'airtel_money',
                    'amount' => 5000,
                    'currency' => 'MWK',
                    'message' => 'Mobile money payment initiated successfully',
                ],
            ]);

        // Verify payment was created in database
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'booking_id' => $this->booking->id,
            'client_id' => $this->client->id,
            'provider' => 'airtel_money',
            'amount' => 5000,
            'phone_number' => '265991234567', // Should be normalized
            'status' => Payment::STATUS_PENDING,
        ]);

        // Verify HTTP request was made to PayChangu
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.paychangu.com/mobile-money/charge' &&
                   $request['provider'] === 'airtel' &&
                   $request['amount'] === 5000 &&
                   $request['phone_number'] === '265991234567';
        });

        Event::assertDispatched(PaymentInitiated::class);
    }

    public function test_can_process_card_payment(): void
    {
        Event::fake([PaymentInitiated::class]);

        // Mock PayChangu API response
        Http::fake([
            'api.paychangu.com/payment' => Http::response([
                'status' => 'success',
                'data' => [
                    'checkout_url' => 'https://checkout.paychangu.com/123456',
                    'data' => [
                        'tx_ref' => 'PAYCHANGU_12345',
                        'status' => 'pending',
                        'amount' => 10000,
                        'currency' => 'MWK',
                    ],
                ],
            ], 200),
        ]);

        $paymentData = [
            'provider' => 'card',
            'amount' => 10000,
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'booking_id' => $this->booking->id,
            'client_id' => $this->client->id,
        ];

        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", $paymentData);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'card',
                    'amount' => 10000,
                    'currency' => 'MWK',
                    'checkout_url' => 'https://checkout.paychangu.com/123456',
                    'message' => 'Card payment session created successfully',
                ],
            ]);

        // Verify payment was created in database
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'provider' => 'card',
            'amount' => 10000,
            'status' => Payment::STATUS_PENDING,
        ]);

        Event::assertDispatched(PaymentInitiated::class);
    }

    public function test_can_check_payment_status(): void
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'external_id' => 'PAYCHANGU_12345',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
        ]);

        // Mock PayChangu verification response
        Http::fake([
            'api.paychangu.com/verify-payment/PAYCHANGU_12345' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'tx_ref' => 'PAYCHANGU_12345',
                    'amount' => 5000,
                    'currency' => 'MWK',
                    'authorization' => [
                        'channel' => 'Mobile Money',
                        'mobile_number' => '265991234567',
                    ],
                    'logs' => [
                        [
                            'type' => 'log',
                            'message' => 'Payment completed successfully',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/t/{$this->tenant->uuid}/payments/{$payment->uuid}/status");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->uuid,
                    'status' => Payment::STATUS_COMPLETED,
                    'amount' => 5000,
                    'currency' => 'MWK',
                ],
            ]);

        // Verify payment status was updated
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->completed_at);
    }

    public function test_can_handle_paychangu_webhook(): void
    {
        Event::fake([PaymentCompleted::class]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'external_id' => 'PAYCHANGU_12345',
            'reference' => 'ADPRO_TEST123',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
        ]);

        $webhookPayload = [
            'event_type' => 'api.charge.payment',
            'status' => 'success',
            'reference' => 'PAYCHANGU_12345',
            'tx_ref' => 'PAYCHANGU_12345',
            'amount' => 5000,
            'currency' => 'MWK',
            'authorization' => [
                'channel' => 'Mobile Money',
                'mobile_number' => '265991234567',
            ],
        ];

        // Mock webhook signature
        $webhookSecret = config('paychangu.webhook_secret', 'test-secret');
        $signature = hash_hmac('sha256', json_encode($webhookPayload), $webhookSecret);

        $response = $this->withHeaders([
            'Signature' => $signature,
        ])->postJson('/api/webhooks/payments/paychangu', $webhookPayload);

        $response->assertSuccessful()
            ->assertJson(['status' => 'success']);

        // Verify payment status was updated
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->completed_at);

        Event::assertDispatched(PaymentCompleted::class, function ($event) use ($payment) {
            return $event->payment_id === $payment->id;
        });
    }

    public function test_can_handle_failed_payment_webhook(): void
    {
        Event::fake([PaymentFailed::class]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'external_id' => 'PAYCHANGU_12345',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
        ]);

        $webhookPayload = [
            'event_type' => 'api.charge.payment',
            'status' => 'failed',
            'reference' => 'PAYCHANGU_12345',
            'tx_ref' => 'PAYCHANGU_12345',
            'amount' => 5000,
            'currency' => 'MWK',
            'message' => 'Insufficient funds',
        ];

        // Mock webhook signature
        $webhookSecret = config('paychangu.webhook_secret', 'test-secret');
        $signature = hash_hmac('sha256', json_encode($webhookPayload), $webhookSecret);

        $response = $this->withHeaders([
            'Signature' => $signature,
        ])->postJson('/api/webhooks/payments/paychangu', $webhookPayload);

        $response->assertSuccessful();

        // Verify payment status was updated
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $payment->status);
        $this->assertEquals('Insufficient funds', $payment->failure_reason);
        $this->assertNotNull($payment->failed_at);

        Event::assertDispatched(PaymentFailed::class, function ($event) use ($payment) {
            return $event->payment_id === $payment->id;
        });
    }

    public function test_validates_payment_request_data(): void
    {
        // Test missing provider
        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", [
            'amount' => 5000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);

        // Test invalid provider
        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", [
            'provider' => 'invalid_provider',
            'amount' => 5000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);

        // Test missing phone number for mobile money
        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", [
            'provider' => 'airtel_money',
            'amount' => 5000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);

        // Test missing email for card payment
        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", [
            'provider' => 'card',
            'amount' => 5000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
    }

    public function test_can_get_payment_history(): void
    {
        // Create some test payments
        $payments = Payment::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson("/api/t/{$this->tenant->uuid}/payments/history");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'pagination' => [
                        'total' => 5,
                    ],
                ],
            ])
            ->assertJsonCount(5, 'data.payments');
    }

    public function test_can_filter_payment_history(): void
    {
        // Create payments with different providers
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'airtel_money',
        ]);

        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'card',
        ]);

        $response = $this->getJson("/api/t/{$this->tenant->uuid}/payments/history?provider=airtel_money");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'pagination' => [
                        'total' => 1,
                    ],
                ],
            ]);

        $this->assertEquals('airtel_money', $response->json('data.payments.0.provider'));
    }

    public function test_paychangu_service_configuration(): void
    {
        $service = new PayChanguService();

        // Test that service can get supported providers
        $providers = $service->getSupportedProviders($this->tenant);

        $this->assertIsArray($providers);
        $this->assertNotEmpty($providers);

        $providerIds = array_column($providers, 'id');
        $this->assertContains('card', $providerIds);
        $this->assertContains('airtel_money', $providerIds);
        $this->assertContains('tnm_mpamba', $providerIds);
        $this->assertContains('bank_transfer', $providerIds);
    }

    public function test_handles_paychangu_api_errors(): void
    {
        // Mock PayChangu API error response
        Http::fake([
            'api.paychangu.com/mobile-money/charge' => Http::response([
                'message' => 'Invalid phone number',
                'status' => 'failed',
            ], 400),
        ]);

        $paymentData = [
            'provider' => 'airtel_money',
            'amount' => 5000,
            'phone_number' => '0991234567',
        ];

        $response = $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payment initiation failed',
            ]);
    }

    public function test_normalizes_phone_numbers(): void
    {
        Event::fake();

        Http::fake([
            'api.paychangu.com/mobile-money/charge' => Http::response([
                'status' => 'success',
                'data' => [
                    'tx_ref' => 'PAYCHANGU_12345',
                    'status' => 'pending',
                ],
            ]),
        ]);

        $testCases = [
            '0991234567' => '265991234567',
            '+265991234567' => '265991234567',
            '265991234567' => '265991234567',
            '991234567' => '265991234567',
        ];

        foreach ($testCases as $input => $expected) {
            $this->postJson("/api/t/{$this->tenant->uuid}/payments/process", [
                'provider' => 'airtel_money',
                'amount' => 1000,
                'phone_number' => $input,
            ]);

            $this->assertDatabaseHas('payments', [
                'phone_number' => $expected,
            ]);

            // Clean up for next iteration
            Payment::query()->delete();
        }
    }
}