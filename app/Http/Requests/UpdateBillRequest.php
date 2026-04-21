<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bill = $this->route('bill');

        return $bill !== null && $bill->user_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'frequency' => ['sometimes', 'required', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'])],
            'interval_days' => ['nullable', 'required_if:frequency,custom', 'integer', 'min:1', 'max:3650'],
            'next_due_on' => ['sometimes', 'required', 'date'],
            'last_paid_on' => ['nullable', 'date', 'before_or_equal:today'],
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
            'last_paid_on' => 'last payment date',
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
            'last_paid_on.before_or_equal' => 'The last payment date can\'t be in the future.',
        ];
    }
}
