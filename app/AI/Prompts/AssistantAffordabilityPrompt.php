<?php

namespace App\AI\Prompts;

class AssistantAffordabilityPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        Answer whether a specific purchase fits the user's current month.
        Compute and state: the cash remaining after the purchase, the per-day
        amount for the days_remaining, and the impact on any flagged category.
        You may not recommend or discourage the purchase — describe impact only.
        If CONTEXT.question contains no dollar amount and none is present in
        CONTEXT, return verdict "tight" with body "I need the amount to check"
        and an empty referenced_values array.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'assistant_affordability',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['answer_type', 'verdict', 'body', 'referenced_values'],
                'properties' => [
                    'answer_type' => ['type' => 'string', 'const' => 'affordability'],
                    'verdict' => ['type' => 'string', 'enum' => ['fits', 'tight', 'does_not_fit']],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
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
        return 350;
    }

    protected function rules(): array
    {
        return [PromptRules::global(), PromptRules::chat()];
    }
}
