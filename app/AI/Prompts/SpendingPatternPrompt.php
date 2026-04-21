<?php

namespace App\AI\Prompts;

class SpendingPatternPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        Surface the single most notable spending shift or pattern in CONTEXT.
        Prefer category-level findings over totals. If no category moved by
        more than 20% or $50 versus the prior period, return severity "info"
        with a one-line stability note. Never fabricate a trend.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'spending_pattern',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['kind', 'severity', 'title', 'body', 'referenced_values'],
                'properties' => [
                    'kind' => ['type' => 'string', 'const' => 'spending_pattern'],
                    'severity' => ['type' => 'string', 'enum' => ['info', 'warning']],
                    'title' => ['type' => 'string', 'maxLength' => 60],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                ],
            ],
        ];
    }
}
