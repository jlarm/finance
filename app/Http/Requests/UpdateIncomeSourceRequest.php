<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncomeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $income = $this->route('income_source');

        return $income !== null && $income->user_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'received_on' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'source',
            'received_on' => 'date received',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'received_on.before_or_equal' => 'Income can\'t be dated in the future.',
        ];
    }
}
