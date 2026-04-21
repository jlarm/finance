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
    /**
     * Plausible expense descriptions by category name. The seeder picks
     * from these to keep the demo data readable in the UI.
     *
     * @var array<string, array<int, string>>
     */
    public const DESCRIPTIONS = [
        'Groceries' => ['Whole Foods run', 'Trader Joe\'s', 'Costco haul', 'Corner store', 'Farmers market'],
        'Dining Out' => ['Lunch with team', 'Takeout dinner', 'Coffee shop', 'Brunch', 'Pizza night'],
        'Transportation' => ['Gas fill-up', 'Uber ride', 'Parking', 'Transit pass', 'Oil change'],
        'Utilities' => ['Electric bill', 'Water bill', 'Internet', 'Gas bill'],
        'Housing' => ['Rent top-up', 'HOA fee', 'Maintenance'],
        'Entertainment' => ['Movie tickets', 'Concert', 'Streaming rental', 'Books'],
        'Health' => ['Pharmacy', 'Doctor visit copay', 'Gym drop-in', 'Vitamins'],
        'Shopping' => ['Amazon order', 'Clothing', 'Home goods', 'Electronics'],
        'Subscriptions' => ['Netflix', 'Spotify', 'Cloud storage', 'Newsletter'],
        'Personal Care' => ['Haircut', 'Toiletries', 'Skincare'],
    ];

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

    public function forCategory(ExpenseCategory $category): static
    {
        return $this->state(fn () => [
            'user_id' => $category->user_id,
            'expense_category_id' => $category->id,
            'description' => fake()->randomElement(
                self::DESCRIPTIONS[$category->name] ?? [fake()->sentence(3)]
            ),
        ]);
    }
}
