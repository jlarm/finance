<?php

namespace App\AI\Prompts;

class SavingsPacingPrompt extends Prompt
{
    public function system(): string
    {
        return <<<'TXT'
        Give pacing feedback on a single savings goal. Pick the goal flagged
        needs_attention = true; if none is flagged, pick the least-funded
        active goal. Compare projected_amount_at_target_date to
        target_amount, and if pacing is short, compute the monthly
        contribution increase that would close the gap by target_date.
        If projected_amount_at_target_date is missing, say pacing can't
        be determined yet rather than guessing.
        TXT;
    }

    public function schema(): array
    {
        return [
            'name' => 'savings_pacing',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['kind', 'severity', 'title', 'body', 'referenced_values'],
                'properties' => [
                    'kind' => ['type' => 'string', 'const' => 'savings_pacing'],
                    'severity' => ['type' => 'string', 'enum' => ['info', 'warning']],
                    'title' => ['type' => 'string', 'maxLength' => 60],
                    'body' => ['type' => 'string'],
                    'referenced_values' => self::referencedValuesSchema(),
                ],
            ],
        ];
    }
}
