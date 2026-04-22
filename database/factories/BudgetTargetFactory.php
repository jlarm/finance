<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\BudgetTarget;
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
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'period_month' => now()->startOfMonth()->toDateString(),
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
