<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Lilongwe', 'Blantyre', 'Mzuzu', 'Zomba']),
            'state' => $this->faker->randomElement(['Central', 'Southern', 'Northern']),
            'country' => 'Malawi',
        ];
    }

    public function withoutCompany(): static
    {
        return $this->state(fn () => ['company' => null]);
    }
}
