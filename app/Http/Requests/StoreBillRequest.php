<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ((string) $this->input('debt_id') === '0') {
            $this->merge(['debt_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'debt_id' => [
                'nullable',
                'integer',
                Rule::exists('debts', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'frequency' => ['required', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'])],
            'interval_days' => ['nullable', 'required_if:frequency,custom', 'integer', 'min:1', 'max:3650'],
            'next_due_on' => ['required', 'date'],
            'autopay_reminder' => ['sometimes', 'boolean'],
            'split_across_paychecks' => ['sometimes', 'boolean'],
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
            'next_due_on' => 'next due date',
            'interval_days' => 'custom interval',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'debt_id.exists' => 'Pick one of your debts.',
            'interval_days.required_if' => 'Set the number of days between payments when the frequency is custom.',
        ];
    }
}
