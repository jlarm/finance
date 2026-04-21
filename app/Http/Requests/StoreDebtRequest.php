<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['credit_card', 'student', 'auto', 'mortgage', 'personal', 'medical', 'other'])],
            'balance' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'original_balance' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99', 'gte:balance'],
            'apr' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'minimum_payment' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'apr' => 'APR',
            'due_day' => 'due day of month',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_balance.gte' => 'The original balance can\'t be less than the current balance.',
            'apr.max' => 'APR should be a percentage between 0 and 100.',
            'due_day.max' => 'The due day must be between 1 and 31.',
        ];
    }
}
