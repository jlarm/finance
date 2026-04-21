<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');

        return $expense !== null && $expense->user_id === $this->user()->id;
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
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'occurred_on' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'description' => ['sometimes', 'required', 'string', 'max:160'],
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
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expense_category_id.exists' => 'Pick one of your categories.',
            'occurred_on.before_or_equal' => 'Expenses can\'t be dated in the future.',
        ];
    }
}
