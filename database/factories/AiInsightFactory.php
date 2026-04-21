<?php

namespace Database\Factories;

use App\Models\AiInsight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiInsight>
 */
class AiInsightFactory extends Factory
{
    /**
     * Hand-written sample insights used by the demo seeder. Shape
     * mirrors what the real generator produces so the UI renders
     * identically whether data comes from seeds or the live pipeline.
     *
     * @var array<int, array{kind: string, severity: string, title: string, body: string}>
     */
    public const SAMPLES = [
        [
            'kind' => 'spending_spike',
            'severity' => 'info',
            'title' => 'Dining out ticked up this month',
            'body' => 'Dining Out is trending higher than your last three months. Consider a weekly cap if you\'re targeting more savings.',
        ],
        [
            'kind' => 'cashflow_warning',
            'severity' => 'warning',
            'title' => 'Three bills cluster the week of the 15th',
            'body' => 'Rent, electric, and car insurance all fall in the same week. Consider moving one or setting cash aside early.',
        ],
        [
            'kind' => 'goal_progress',
            'severity' => 'info',
            'title' => 'Emergency Fund is on pace',
            'body' => 'At your current contribution rate, you\'ll reach the target about two months ahead of schedule. Nice.',
        ],
        [
            'kind' => 'debt_progress',
            'severity' => 'info',
            'title' => 'Visa is your highest-APR debt',
            'body' => 'Avalanche method suggests directing any extra payment to the Visa balance first at 22.99% APR.',
        ],
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => fake()->randomElement([
                'spending_spike', 'bill_reminder', 'debt_progress',
                'goal_progress', 'cashflow_warning', 'budget_overrun', 'tip',
            ]),
            'severity' => 'info',
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'data' => null,
            'status' => 'new',
            'generated_for_period' => now()->startOfMonth()->toDateString(),
        ];
    }

    public function dismissed(): static
    {
        return $this->state(fn () => ['status' => 'dismissed']);
    }

    public function actedOn(): static
    {
        return $this->state(fn () => ['status' => 'acted_on']);
    }

    public function critical(): static
    {
        return $this->state(fn () => ['severity' => 'critical']);
    }

    public function warning(): static
    {
        return $this->state(fn () => ['severity' => 'warning']);
    }
}
