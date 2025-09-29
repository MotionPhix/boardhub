<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 1000, 50000);

        return [
            'tenant_id' => Tenant::factory(),
            'booking_id' => Booking::factory(),
            'client_id' => Client::factory(),
            'provider' => $this->faker->randomElement([
                Payment::PROVIDER_AIRTEL_MONEY,
                Payment::PROVIDER_TNM_MPAMBA,
                Payment::PROVIDER_CARD,
                Payment::PROVIDER_BANK_TRANSFER,
            ]),
            'amount' => $amount,
            'currency' => 'MWK',
            'phone_number' => $this->faker->optional()->numerify('265991234567'),
            'reference' => 'ADPRO_'.strtoupper($this->faker->unique()->lexify('?????')),
            'external_id' => 'PAYCHANGU_'.$this->faker->unique()->randomNumber(8),
            'status' => $this->faker->randomElement([
                Payment::STATUS_PENDING,
                Payment::STATUS_PROCESSING,
                Payment::STATUS_COMPLETED,
                Payment::STATUS_FAILED,
            ]),
            'failure_reason' => null,
            'provider_response' => [],
            'metadata' => [],
            'initiated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'completed_at' => null,
            'failed_at' => null,
            'status_checked_at' => $this->faker->optional()->dateTimeBetween('-1 day', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PENDING,
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_COMPLETED,
            'completed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'failed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => $this->faker->randomElement([
                'Insufficient funds',
                'Invalid phone number',
                'Network timeout',
                'Transaction declined',
            ]),
            'completed_at' => null,
            'failed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function airtelMoney(): static
    {
        return $this->state(fn () => [
            'provider' => Payment::PROVIDER_AIRTEL_MONEY,
            'phone_number' => '265991234567',
        ]);
    }

    public function tnmMpamba(): static
    {
        return $this->state(fn () => [
            'provider' => Payment::PROVIDER_TNM_MPAMBA,
            'phone_number' => '265881234567',
        ]);
    }

    public function card(): static
    {
        return $this->state(fn () => [
            'provider' => Payment::PROVIDER_CARD,
            'phone_number' => null,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn () => [
            'provider' => Payment::PROVIDER_BANK_TRANSFER,
            'phone_number' => null,
        ]);
    }
}
