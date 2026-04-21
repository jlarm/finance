<?php

namespace App\AI\Prompts;

class DebtCoachingPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        Coach the user on debt payoff progress for the strategy in CONTEXT.
        Cite the single debt to focus on next (CONTEXT.target_debt) by name,
        balance, and APR, plus the projected debt-free date. If CONTEXT
        .feasible is false, say the current budget does not cover this
        month's interest — no blame, no lecture. Do not compare strategies
        unless another is present in CONTEXT.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'debt_coaching',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['kind', 'severity', 'title', 'body', 'referenced_values'],
                'properties' => [
                    'kind' => ['type' => 'string', 'const' => 'debt_coaching'],
                    'severity' => ['type' => 'string', 'enum' => ['info', 'warning']],
                    'title' => ['type' => 'string', 'maxLength' => 60],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                ],
            ],
        ];
    }
}
