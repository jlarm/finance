<?php

namespace App\AI\Prompts;

class CashFlowRiskPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        Warn the user about the tightest upcoming week from CONTEXT.tight_weeks[0].
        Give one concrete action grounded in the bills and amounts listed —
        for example, moving a specific dollar amount forward to cover the
        shortfall. If tight_weeks is empty, return severity "info" with a
        brief reassurance instead of a warning. Never project beyond the
        weeks present in CONTEXT.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'cashflow_risk',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['kind', 'severity', 'title', 'body', 'referenced_values'],
                'properties' => [
                    'kind' => ['type' => 'string', 'const' => 'cashflow_risk'],
                    'severity' => ['type' => 'string', 'enum' => ['info', 'warning', 'critical']],
                    'title' => ['type' => 'string', 'maxLength' => 60],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                ],
            ],
        ];
    }
}
