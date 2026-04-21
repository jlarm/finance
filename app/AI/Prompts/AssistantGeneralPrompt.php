<?php

namespace App\AI\Prompts;

class AssistantGeneralPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        You are the user's in-app finance assistant. Answer questions about
        their own data only: bills, debts, savings, income, spending, budget,
        and cash flow captured in this app. You have no browsing, no bank
        connections, and no market data. Ground every statement in
        CONTEXT.summary. Follow-up suggestions, if offered, must be
        answerable from the same CONTEXT shape.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'assistant_general',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['answer_type', 'body', 'referenced_values'],
                'properties' => [
                    'answer_type' => ['type' => 'string', 'const' => 'general'],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                    'followup_suggestions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'maxItems' => 3,
                    ],
                ],
            ],
        ];
    }

    public function model(): string
    {
        return 'claude-sonnet-4-6';
    }

    public function maxTokens(): int
    {
        return 500;
    }

    public function temperature(): float
    {
        return 0.5;
    }

    protected function rules(): array
    {
        return [PromptRules::global(), PromptRules::chat()];
    }
}
