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
}
