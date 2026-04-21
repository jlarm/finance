<?php

namespace Database\Factories;

use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeSource>
 */
class IncomeSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Employer', 'Freelance', 'Side Gig', 'Dividends', 'Gift']),
            'amount' => fake()->randomFloat(2, 200, 6000),
            'received_on' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'notes' => null,
        ];
    }

    public function paycheck(): static
    {
        return $this->state(fn () => [
            'name' => 'Acme Corp — Paycheck',
            'amount' => fake()->randomFloat(2, 2200, 2600),
        ]);
    }

    public function freelance(): static
    {
        return $this->state(fn () => [
            'name' => 'Freelance Invoice',
            'amount' => fake()->randomFloat(2, 400, 1800),
        ]);
    }
}
