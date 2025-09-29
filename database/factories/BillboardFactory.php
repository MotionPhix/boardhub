<?php

namespace Database\Factories;

use App\Models\Billboard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billboard>
 */
class BillboardFactory extends Factory
{
    protected $model = Billboard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'name' => $this->faker->streetName().' Billboard',
            'location' => $this->faker->streetAddress().', '.$this->faker->city(),
            'size' => $this->faker->randomElement(['4x6m', '8x12m', '10x15m', '6x9m']),
            'price' => $this->faker->randomFloat(2, 5000, 50000),
            'status' => $this->faker->randomElement([
                Billboard::STATUS_AVAILABLE,
                Billboard::STATUS_OCCUPIED,
                Billboard::STATUS_MAINTENANCE,
            ]),
            'description' => $this->faker->sentence(),
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => Billboard::STATUS_AVAILABLE]);
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => Billboard::STATUS_OCCUPIED]);
    }
}
