<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly spending totals for the dashboard's Spending report.
 *
 * Decision cues surfaced: current vs prior month, trailing average,
 * and peak month inside the range.
 */
class MonthlySpendingReport
{
    private const DEFAULT_MONTHS = 6;

    /**
     * @return array{
     *     range: array{from: string, to: string},
     *     totals: array{
     *         this_month: float,
     *         prior_month: float,
     *         average_monthly: float,
     *         peak_month: ?array{month: string, total: float}
     *     },
     *     by_month: array<int, array{month: string, total: float}>,
     *     by_category_current_month: array<int, array{
     *         category_id: int, name: string, color: ?string, total: float
     *     }>
     * }
     */
    public function build(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $to = ($to ?? today())->copy()->endOfMonth();
        $from = ($from ?? today()->copy()->subMonths(self::DEFAULT_MONTHS - 1))->copy()->startOfMonth();

        $byMonth = $this->byMonth($user, $from, $to);

        $thisKey = today()->format('Y-m');
        $priorKey = today()->copy()->subMonthNoOverflow()->format('Y-m');

        $thisMonth = (float) ($byMonth[$thisKey] ?? 0);
        $priorMonth = (float) ($byMonth[$priorKey] ?? 0);

        $average = count($byMonth) > 0 ? round(array_sum($byMonth) / count($byMonth), 2) : 0.0;

        $peak = null;

        foreach ($byMonth as $month => $total) {
            if ($peak === null || $total > $peak['total']) {
                $peak = ['month' => $month, 'total' => round((float) $total, 2)];
            }
        }

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'totals' => [
                'this_month' => round($thisMonth, 2),
                'prior_month' => round($priorMonth, 2),
                'average_monthly' => $average,
                'peak_month' => $peak,
            ],
            'by_month' => array_map(
                fn (string $month, float $total): array => [
                    'month' => $month,
                    'total' => round($total, 2),
                ],
                array_keys($byMonth),
                array_values($byMonth),
            ),
            'by_category_current_month' => $this->byCategoryForMonth($user, today()),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function byMonth(User $user, Carbon $from, Carbon $to): array
    {
        $expression = $this->monthExpression('occurred_on');

        return $user->expenses()
            ->whereBetween('occurred_on', [$from, $to])
            ->selectRaw("{$expression} as month, SUM(amount) as total")
            ->groupBy(DB::raw($expression))
            ->orderBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * @return array<int, array{category_id: int, name: string, color: ?string, total: float}>
     */
    private function byCategoryForMonth(User $user, Carbon $month): array
    {
        return $user->expenses()
            ->with('category:id,name,color')
            ->whereBetween('occurred_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'category_id' => (int) $row->expense_category_id,
                'name' => (string) ($row->category?->name ?? 'Uncategorized'),
                'color' => $row->category?->color,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
