<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
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
            'expense_category_id' => [
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'frequency' => ['required', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'])],
            'interval_days' => ['nullable', 'required_if:frequency,custom', 'integer', 'min:1', 'max:3650'],
            'next_due_on' => ['required', 'date'],
            'autopay_reminder' => ['sometimes', 'boolean'],
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
            'expense_category_id' => 'category',
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
            'expense_category_id.exists' => 'Pick one of your categories.',
            'interval_days.required_if' => 'Set the number of days between payments when the frequency is custom.',
        ];
    }
}
