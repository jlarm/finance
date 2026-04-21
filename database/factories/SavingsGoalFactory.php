<?php

namespace Database\Factories;

use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
{
    public function definition(): array
    {
        $target = fake()->randomFloat(2, 500, 20000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Emergency Fund', 'Vacation', 'New Car', 'Home Down Payment', 'Holidays']),
            'target_amount' => $target,
            'current_amount' => fake()->randomFloat(2, 0, $target),
            'target_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            'is_achieved' => false,
            'notes' => null,
        ];
    }

    public function achieved(): static
    {
        return $this->state(fn (array $attrs) => [
            'current_amount' => $attrs['target_amount'],
            'is_achieved' => true,
        ]);
    }
}
