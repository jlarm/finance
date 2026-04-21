<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Per-category monthly spending totals with a simple up/down/flat signal
 * derived from comparing the first-half vs. second-half averages across
 * the range. MVP-friendly — no regression or seasonality.
 */
class CategoryTrendsReport
{
    /** Change threshold (%) below which direction is 'flat'. */
    private const FLAT_THRESHOLD_PCT = 10.0;

    /**
     * @return array{
     *     range: array{from: string, to: string, months: int},
     *     categories: array<int, array{
     *         category_id: int,
     *         name: string,
     *         color: ?string,
     *         series: array<int, array{month: string, total: float}>,
     *         total: float,
     *         average_monthly: float,
     *         direction: 'up'|'down'|'flat',
     *         change_pct: float
     *     }>
     * }
     */
    public function build(User $user, int $months = 6): array
    {
        $months = max(2, min(24, $months));

        $to = today()->copy()->endOfMonth();
        $from = today()->copy()->subMonths($months - 1)->startOfMonth();

        $monthKeys = $this->monthKeys($from, $to);
        $expression = $this->monthExpression('occurred_on');

        $rows = $user->expenses()
            ->with('category:id,name,color')
            ->whereBetween('occurred_on', [$from, $to])
            ->selectRaw("expense_category_id, {$expression} as month, SUM(amount) as total")
            ->groupBy('expense_category_id', DB::raw($expression))
            ->orderBy('expense_category_id')
            ->orderBy('month')
            ->get();

        $grouped = $rows->groupBy('expense_category_id');

        $categories = $grouped->map(function ($rows, int $categoryId) use ($monthKeys): array {
            $category = $rows->first()?->category;

            $series = [];
            $total = 0.0;

            foreach ($monthKeys as $month) {
                $row = $rows->firstWhere('month', $month);
                $value = round((float) ($row->total ?? 0), 2);
                $series[] = ['month' => $month, 'total' => $value];
                $total += $value;
            }

            return [
                'category_id' => $categoryId,
                'name' => (string) ($category?->name ?? 'Uncategorized'),
                'color' => $category?->color,
                'series' => $series,
                'total' => round($total, 2),
                'average_monthly' => round($total / max(count($monthKeys), 1), 2),
                'direction' => $this->direction($series),
                'change_pct' => $this->changePct($series),
            ];
        })
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'months' => $months,
            ],
            'categories' => $categories,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function monthKeys(Carbon $from, Carbon $to): array
    {
        $keys = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor <= $to) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonthNoOverflow();
        }

        return $keys;
    }

    /**
     * @param  array<int, array{month: string, total: float}>  $series
     */
    private function direction(array $series): string
    {
        $pct = $this->changePct($series);

        if (abs($pct) < self::FLAT_THRESHOLD_PCT) {
            return 'flat';
        }

        return $pct > 0 ? 'up' : 'down';
    }

    /**
     * @param  array<int, array{month: string, total: float}>  $series
     */
    private function changePct(array $series): float
    {
        $count = count($series);

        if ($count < 2) {
            return 0.0;
        }

        $mid = (int) floor($count / 2);
        $firstHalf = array_slice($series, 0, $mid);
        $secondHalf = array_slice($series, $mid);

        $firstAvg = array_sum(array_column($firstHalf, 'total')) / max(count($firstHalf), 1);
        $secondAvg = array_sum(array_column($secondHalf, 'total')) / max(count($secondHalf), 1);

        if ($firstAvg <= 0.0) {
            return $secondAvg > 0 ? 100.0 : 0.0;
        }

        return round((($secondAvg - $firstAvg) / $firstAvg) * 100, 1);
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
