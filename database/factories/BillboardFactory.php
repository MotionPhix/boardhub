<?php

namespace Database\Factories;

use App\Models\Billboard;
use App\Models\Location;
use App\Models\Tenant;
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
            'tenant_id' => Tenant::factory(),
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->streetName().' Billboard',
            'code' => 'BB-'.strtoupper($this->faker->unique()->lexify('?????')),
            // Old schema fields (still required in SQLite)
            'location_id' => Location::factory(),
            'size' => $this->faker->randomElement(['4x6m', '8x12m', '10x15m', '6x9m']),
            'base_price' => $this->faker->randomFloat(2, 5000, 50000),
            'currency_code' => 'MWK',
            'physical_status' => $this->faker->randomElement(['good', 'fair', 'needs_repair']),
            'latitude' => $this->faker->latitude(-17, -9), // Malawi latitude range
            'longitude' => $this->faker->longitude(32, 36), // Malawi longitude range
            'site' => $this->faker->optional()->streetAddress(),
            'is_active' => true,
            'meta_data' => null,
            'description' => $this->faker->sentence(),
            // New schema fields
            'location' => $this->faker->city().' - '.$this->faker->streetName(),
            'price' => $this->faker->randomFloat(2, 5000, 50000),
            'status' => $this->faker->randomElement(['available', 'occupied', 'maintenance']),
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => 'available']);
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => 'occupied']);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => 'maintenance']);
    }
}
