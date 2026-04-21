<?php

namespace App\Services\AI;

use App\AI\Prompts\CashFlowRiskPrompt;
use App\AI\Prompts\DebtCoachingPrompt;
use App\AI\Prompts\MonthlySummaryPrompt;
use App\AI\Prompts\Prompt;
use App\AI\Prompts\SavingsPacingPrompt;
use App\AI\Prompts\SpendingPatternPrompt;
use InvalidArgumentException;

/**
 * Thin dispatcher that resolves an AI prompt class for an insight kind.
 * Keeps kind-to-prompt wiring in one place so FinancialCoachService doesn't
 * hard-code class names inline.
 */
class InsightPromptBuilder
{
    /**
     * @return array<string, class-string<Prompt>>
     */
    public static function registry(): array
    {
        return [
            'monthly_summary' => MonthlySummaryPrompt::class,
            'spending_pattern' => SpendingPatternPrompt::class,
            'cashflow_risk' => CashFlowRiskPrompt::class,
            'debt_coaching' => DebtCoachingPrompt::class,
            'savings_pacing' => SavingsPacingPrompt::class,
        ];
    }

    public function for(string $kind): Prompt
    {
        $class = self::registry()[$kind] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown insight kind: {$kind}");
        }

        return app($class);
    }

    public function supports(string $kind): bool
    {
        return array_key_exists($kind, self::registry());
    }
}
