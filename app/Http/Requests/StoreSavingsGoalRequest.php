<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsGoalRequest extends FormRequest
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
            'target_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'current_amount' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99', 'lte:target_amount'],
            'target_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target_amount' => 'target',
            'current_amount' => 'current balance',
            'target_date' => 'target date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_amount.lte' => 'The current balance can\'t exceed the target.',
            'target_date.after' => 'Pick a target date in the future.',
        ];
    }
}
