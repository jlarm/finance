<?php

namespace Database\Factories;

use App\Models\BudgetTarget;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetTarget>
 */
class BudgetTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_category_id' => fn (array $attributes) => ExpenseCategory::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
