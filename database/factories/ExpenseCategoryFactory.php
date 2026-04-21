<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'icon' => null,
            'color' => fake()->hexColor(),
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
