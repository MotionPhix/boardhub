<?php

namespace Database\Factories;

use App\Models\Billboard;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, '+60 days');
        $requestedPrice = $this->faker->randomFloat(2, 5000, 50000);

        return [
            'tenant_id' => Tenant::factory(),
            'billboard_id' => Billboard::factory(),
            'client_id' => Client::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'requested_price' => $requestedPrice,
            'final_price' => $this->faker->boolean(70) ? $requestedPrice : $this->faker->randomFloat(2, $requestedPrice * 0.8, $requestedPrice * 1.2),
            'campaign_details' => $this->faker->sentence(),
            'status' => $this->faker->randomElement([
                Booking::STATUS_REQUESTED,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_REJECTED,
                Booking::STATUS_CANCELLED,
                Booking::STATUS_COMPLETED,
            ]),
            'confirmed_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'price_negotiations' => [],
            'status_history' => [],
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_REQUESTED]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => Booking::STATUS_CONFIRMED,
            'confirmed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => Booking::STATUS_REJECTED,
            'rejected_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_CANCELLED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_COMPLETED]);
    }
}
