<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('budget_target');

        return $target !== null && $target->user_id === $this->user()->id;
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
        $target = $this->route('budget_target');

        return [
            'category' => ['sometimes', 'required', Rule::enum(ExpenseCategory::class)],
            'period_month' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('budget_targets')
                    ->ignore($target?->id)
                    ->where(fn ($q) => $q
                        ->where('user_id', $this->user()->id)
                        ->where('category', $this->input('category', $target?->category?->value))
                        ->where('period_month', $this->input('period_month'))
                    ),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'period_month' => 'month',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period_month.unique' => 'You already have a budget target for this category in that month.',
        ];
    }
}
