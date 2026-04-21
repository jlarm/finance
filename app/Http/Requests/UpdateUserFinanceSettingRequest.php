<?php

namespace App\Http\Requests;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserFinanceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'alpha', 'uppercase'],
            'locale' => ['sometimes', 'required', 'string', 'max:10'],
            'monthly_cycle_start_day' => ['sometimes', 'required', 'integer', 'min:1', 'max:28'],
            'debt_strategy' => ['sometimes', 'required', Rule::in(['snowball', 'avalanche'])],
            'ai_tone' => ['sometimes', 'required', Rule::in(['supportive', 'direct', 'cheerful', 'neutral'])],
            'ai_enabled' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'required', 'string', 'max:64', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monthly_cycle_start_day' => 'monthly cycle start day',
            'debt_strategy' => 'debt payoff strategy',
            'ai_tone' => 'AI tone',
            'ai_enabled' => 'AI features',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.size' => 'Use a 3-letter currency code (e.g. USD, EUR).',
            'currency.uppercase' => 'Currency codes should be uppercase.',
            'monthly_cycle_start_day.max' => 'Pick a day between 1 and 28 so every month has it.',
            'timezone.in' => 'Choose a valid timezone.',
        ];
    }
}
