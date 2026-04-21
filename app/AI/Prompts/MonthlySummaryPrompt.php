<?php

namespace App\AI\Prompts;

class MonthlySummaryPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        You are the user's finance companion, writing their monthly recap.
        Summarize what happened this month: income, spending, net, a notable
        category shift if one is present, and a single actionable next step
        for the upcoming month. Reference exact category names and amounts
        from CONTEXT.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'monthly_summary',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['kind', 'severity', 'title', 'body', 'referenced_values'],
                'properties' => [
                    'kind' => ['type' => 'string', 'const' => 'monthly_summary'],
                    'severity' => ['type' => 'string', 'enum' => ['info', 'warning', 'critical']],
                    'title' => ['type' => 'string', 'maxLength' => 60],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                ],
            ],
        ];
    }
}
