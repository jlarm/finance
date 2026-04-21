<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    public function definition(): array
    {
        $original = fake()->randomFloat(2, 1000, 25000);
        $balance = fake()->randomFloat(2, 0, $original);

        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Card', 'Loan']),
            'type' => fake()->randomElement(['credit_card', 'student', 'auto', 'personal']),
            'balance' => $balance,
            'original_balance' => $original,
            'apr' => fake()->randomFloat(2, 3, 29.99),
            'minimum_payment' => fake()->randomFloat(2, 25, 500),
            'due_day' => fake()->numberBetween(1, 28),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function paidOff(): static
    {
        return $this->state(fn () => [
            'balance' => 0,
            'is_active' => false,
        ]);
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
