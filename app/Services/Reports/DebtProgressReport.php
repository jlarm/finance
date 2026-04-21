<?php

namespace App\Services\Reports;

use App\Models\Debt;
use App\Models\User;

/**
 * Snapshot of debt progress: balance vs. original, percent paid, and
 * carrying details (APR, minimum). Intended to answer "am I making
 * progress?" at a glance without running the payoff simulator.
 */
class DebtProgressReport
{
    /**
     * @return array{
     *     debts: array<int, array{
     *         id: int,
     *         name: string,
     *         type: string,
     *         balance: float,
     *         original_balance: ?float,
     *         paid_so_far: float,
     *         progress_pct: float,
     *         apr: ?float,
     *         minimum_payment: ?float,
     *         is_active: bool
     *     }>,
     *     totals: array{
     *         balance: float,
     *         original: float,
     *         paid_so_far: float,
     *         progress_pct: float,
     *         active_count: int
     *     }
     * }
     */
    public function build(User $user): array
    {
        $debts = $user->debts()
            ->orderBy('is_active', 'desc')
            ->orderByDesc('balance')
            ->get();

        $items = $debts->map(fn (Debt $debt): array => $this->transform($debt))->all();

        $totalBalance = array_sum(array_column($items, 'balance'));
        $totalOriginal = array_sum(array_map(
            fn (array $item): float => (float) ($item['original_balance'] ?? $item['balance']),
            $items,
        ));
        $totalPaid = max(0.0, $totalOriginal - $totalBalance);
        $totalProgress = $totalOriginal > 0 ? round(($totalPaid / $totalOriginal) * 100, 1) : 0.0;
        $activeCount = count(array_filter($items, fn (array $item): bool => $item['is_active']));

        return [
            'debts' => $items,
            'totals' => [
                'balance' => round($totalBalance, 2),
                'original' => round($totalOriginal, 2),
                'paid_so_far' => round($totalPaid, 2),
                'progress_pct' => $totalProgress,
                'active_count' => $activeCount,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Debt $debt): array
    {
        $balance = (float) $debt->balance;
        $original = $debt->original_balance !== null ? (float) $debt->original_balance : null;
        $paid = $original !== null ? max(0.0, $original - $balance) : 0.0;
        $progress = $original !== null && $original > 0
            ? round(($paid / $original) * 100, 1)
            : 0.0;

        return [
            'id' => (int) $debt->id,
            'name' => (string) $debt->name,
            'type' => (string) $debt->type,
            'balance' => round($balance, 2),
            'original_balance' => $original !== null ? round($original, 2) : null,
            'paid_so_far' => round($paid, 2),
            'progress_pct' => $progress,
            'apr' => $debt->apr !== null ? (float) $debt->apr : null,
            'minimum_payment' => $debt->minimum_payment !== null ? round((float) $debt->minimum_payment, 2) : null,
            'is_active' => (bool) $debt->is_active,
        ];
    }
}
