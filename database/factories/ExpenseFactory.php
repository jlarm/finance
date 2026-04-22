<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Plausible expense descriptions per category. The seeder picks
     * from these to keep the demo data readable in the UI.
     *
     * @var array<string, array<int, string>>
     */
    public const DESCRIPTIONS = [
        'groceries' => ['Whole Foods run', 'Trader Joe\'s', 'Costco haul', 'Corner store', 'Farmers market'],
        'dining_out' => ['Lunch with team', 'Takeout dinner', 'Coffee shop', 'Brunch', 'Pizza night'],
        'transportation' => ['Gas fill-up', 'Uber ride', 'Parking', 'Transit pass', 'Oil change'],
        'utilities' => ['Electric bill', 'Water bill', 'Internet', 'Gas bill'],
        'housing' => ['Rent top-up', 'HOA fee', 'Maintenance'],
        'entertainment' => ['Movie tickets', 'Concert', 'Streaming rental', 'Books'],
        'health' => ['Pharmacy', 'Doctor visit copay', 'Gym drop-in', 'Vitamins'],
        'shopping' => ['Amazon order', 'Clothing', 'Home goods', 'Electronics'],
        'subscriptions' => ['Netflix', 'Spotify', 'Cloud storage', 'Newsletter'],
        'personal_care' => ['Haircut', 'Toiletries', 'Skincare'],
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 5, 500),
            'occurred_on' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(3),
            'notes' => null,
        ];
    }

    public function forCategory(ExpenseCategory $category): static
    {
        return $this->state(fn () => [
            'category' => $category,
            'description' => fake()->randomElement(
                self::DESCRIPTIONS[$category->value] ?? [fake()->sentence(3)]
            ),
        ]);
    }
}
