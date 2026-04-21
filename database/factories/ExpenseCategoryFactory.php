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
    /**
     * Realistic default categories used by the demo seeder. Kept as a
     * tuple list so the seeder can seed deterministic colors too.
     *
     * @var array<int, array{name: string, color: string}>
     */
    public const DEFAULTS = [
        ['name' => 'Groceries', 'color' => '#16a34a'],
        ['name' => 'Dining Out', 'color' => '#f97316'],
        ['name' => 'Transportation', 'color' => '#0ea5e9'],
        ['name' => 'Utilities', 'color' => '#6366f1'],
        ['name' => 'Housing', 'color' => '#7c3aed'],
        ['name' => 'Entertainment', 'color' => '#ec4899'],
        ['name' => 'Health', 'color' => '#14b8a6'],
        ['name' => 'Shopping', 'color' => '#f59e0b'],
        ['name' => 'Subscriptions', 'color' => '#a855f7'],
        ['name' => 'Personal Care', 'color' => '#e11d48'],
    ];

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
