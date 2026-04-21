<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserFinanceSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFinanceSetting>
 */
class UserFinanceSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency' => 'USD',
            'locale' => 'en-US',
            'monthly_cycle_start_day' => 1,
            'debt_strategy' => 'avalanche',
            'ai_tone' => 'supportive',
            'ai_enabled' => true,
            'timezone' => 'UTC',
        ];
    }
}
