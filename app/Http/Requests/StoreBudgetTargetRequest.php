<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('period_month')) {
            $this->merge([
                'period_month' => date('Y-m-01', strtotime((string) $this->input('period_month'))),
            ]);
        }
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
            'period_month' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('budget_targets')->where(fn ($q) => $q
                    ->where('user_id', $this->user()->id)
                    ->where('expense_category_id', $this->input('expense_category_id'))
                    ->where('period_month', $this->input('period_month'))
                ),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_category_id' => 'category',
            'period_month' => 'month',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expense_category_id.exists' => 'Pick one of your categories.',
            'period_month.unique' => 'You already have a budget target for this category in that month.',
        ];
    }
}
