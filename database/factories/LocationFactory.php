<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        // Map city codes to their corresponding state codes from our seeder
        $cityStateMap = [
            'LL' => 'C', // Lilongwe -> Central
            'KS' => 'C', // Kasungu -> Central
            'SL' => 'C', // Salima -> Central
            'BT' => 'S', // Blantyre -> Southern
            'ZB' => 'S', // Zomba -> Southern
            'CH' => 'S', // Chiradzulu -> Southern
            'KR' => 'S', // Karonga -> Southern (corrected from Northern)
            'MZ' => 'N', // Mzuzu -> Northern
            'RP' => 'N', // Rumphi -> Northern
            'NB' => 'N', // Nkhata Bay -> Northern
        ];

        $cityCode = $this->faker->randomElement(array_keys($cityStateMap));
        $stateCode = $cityStateMap[$cityCode];

        return [
            'name' => $this->faker->streetName().' Area',
            'code' => $cityCode.'-'.str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'description' => $this->faker->sentence(),
            'city_code' => $cityCode,
            'state_code' => $stateCode,
            'country_code' => 'MW', // Malawi
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function lilongwe(): static
    {
        return $this->state(fn () => [
            'city_code' => 'LL',
            'state_code' => 'C',
        ]);
    }

    public function blantyre(): static
    {
        return $this->state(fn () => [
            'city_code' => 'BT',
            'state_code' => 'S',
        ]);
    }

    public function mzuzu(): static
    {
        return $this->state(fn () => [
            'city_code' => 'MZ',
            'state_code' => 'N',
        ]);
    }
}
