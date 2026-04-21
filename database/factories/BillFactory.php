<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    /**
     * Realistic bill archetypes used by the demo seeder.
     *
     * @var array<int, array{name: string, amount: float, frequency: string, category: string}>
     */
    public const DEFAULTS = [
        ['name' => 'Rent', 'amount' => 1450.00, 'frequency' => 'monthly', 'category' => 'Housing'],
        ['name' => 'Electric', 'amount' => 95.00, 'frequency' => 'monthly', 'category' => 'Utilities'],
        ['name' => 'Internet', 'amount' => 65.00, 'frequency' => 'monthly', 'category' => 'Utilities'],
        ['name' => 'Phone', 'amount' => 55.00, 'frequency' => 'monthly', 'category' => 'Utilities'],
        ['name' => 'Netflix', 'amount' => 15.49, 'frequency' => 'monthly', 'category' => 'Subscriptions'],
        ['name' => 'Spotify', 'amount' => 10.99, 'frequency' => 'monthly', 'category' => 'Subscriptions'],
        ['name' => 'Gym', 'amount' => 32.00, 'frequency' => 'monthly', 'category' => 'Health'],
        ['name' => 'Car Insurance', 'amount' => 420.00, 'frequency' => 'quarterly', 'category' => 'Transportation'],
        ['name' => 'Amazon Prime', 'amount' => 139.00, 'frequency' => 'annual', 'category' => 'Subscriptions'],
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_category_id' => fn (array $attributes) => ExpenseCategory::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->id,
            'name' => fake()->company(),
            'amount' => fake()->randomFloat(2, 10, 800),
            'frequency' => 'monthly',
            'interval_days' => null,
            'next_due_on' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'last_paid_on' => null,
            'autopay_reminder' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'next_due_on' => now()->subDays(fake()->numberBetween(1, 10))->toDateString(),
        ]);
    }

    public function custom(int $intervalDays): static
    {
        return $this->state(fn () => [
            'frequency' => 'custom',
            'interval_days' => $intervalDays,
        ]);
    }
}
