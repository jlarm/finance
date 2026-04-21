<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_category_id' => fn (array $attributes) => ExpenseCategory::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->id,
            'amount' => fake()->randomFloat(2, 5, 500),
            'occurred_on' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(3),
            'notes' => null,
        ];
    }
}
