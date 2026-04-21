<?php

namespace App\Services\Reports;

use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Per-goal savings progress with a "pace" heuristic.
 *
 * Pace = current_amount / months since goal was created (floor at 1).
 * On-track = projected total by target_date >= target_amount.
 *
 * MVP-friendly: we don't have a contribution ledger, so we use
 * elapsed-time pace as a proxy. Swap in a real contributions table
 * later without changing the payload shape.
 */
class SavingsProgressReport
{
    /**
     * @return array{
     *     goals: array<int, array{
     *         id: int,
     *         name: string,
     *         current_amount: float,
     *         target_amount: float,
     *         progress_pct: float,
     *         target_date: ?string,
     *         monthly_pace: float,
     *         months_remaining: ?int,
     *         projected_total: ?float,
     *         on_track: ?bool,
     *         is_achieved: bool
     *     }>,
     *     totals: array{current: float, target: float, progress_pct: float}
     * }
     */
    public function build(User $user): array
    {
        $goals = $user->savingsGoals()
            ->orderBy('is_achieved')
            ->orderBy('target_date')
            ->get();

        $today = today();

        $items = $goals->map(fn (SavingsGoal $goal): array => $this->transform($goal, $today))->all();

        $totalCurrent = array_sum(array_column($items, 'current_amount'));
        $totalTarget = array_sum(array_column($items, 'target_amount'));
        $totalProgress = $totalTarget > 0 ? round(($totalCurrent / $totalTarget) * 100, 1) : 0.0;

        return [
            'goals' => $items,
            'totals' => [
                'current' => round($totalCurrent, 2),
                'target' => round($totalTarget, 2),
                'progress_pct' => $totalProgress,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(SavingsGoal $goal, Carbon $today): array
    {
        $current = (float) $goal->current_amount;
        $target = (float) $goal->target_amount;
        $progress = $target > 0 ? round(($current / $target) * 100, 1) : 0.0;

        $monthsActive = max(1, (int) $goal->created_at?->diffInMonths($today) ?: 1);
        $pace = round($current / $monthsActive, 2);

        $monthsRemaining = null;
        $projected = null;
        $onTrack = null;

        if ($goal->target_date !== null) {
            $monthsRemaining = max(0, (int) $today->diffInMonths($goal->target_date, false));
            $projected = round($current + $pace * $monthsRemaining, 2);
            $onTrack = $projected >= $target;
        }

        return [
            'id' => (int) $goal->id,
            'name' => (string) $goal->name,
            'current_amount' => round($current, 2),
            'target_amount' => round($target, 2),
            'progress_pct' => $progress,
            'target_date' => $goal->target_date?->toDateString(),
            'monthly_pace' => $pace,
            'months_remaining' => $monthsRemaining,
            'projected_total' => $projected,
            'on_track' => $onTrack,
            'is_achieved' => (bool) $goal->is_achieved,
        ];
    }
}
