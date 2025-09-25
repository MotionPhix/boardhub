<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');
        $total = $this->faker->randomFloat(2, 10000, 200000);
        $discount = $this->faker->randomFloat(2, 0, $total * 0.2);

        return [
            'client_id' => Client::factory(),
            'contract_number' => 'CNT-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_total' => $total,
            'contract_discount' => $discount,
            'contract_final_amount' => $total - $discount,
            'currency_code' => 'MWK',
            'agreement_status' => $this->faker->randomElement(['draft', 'active', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->paragraph(),
            'notification_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['agreement_status' => 'active']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['agreement_status' => 'draft']);
    }
}
