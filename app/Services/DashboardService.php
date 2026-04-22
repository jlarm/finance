<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\Bill;
use App\Models\BudgetTarget;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\IncomeSource;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private int $upcomingWindowDays = 14, private int $recentInsightLimit = 3) {}

    /**
     * Build the full dashboard payload for the given user.
     *
     * @return array{
     *     period: array{start: string, end: string, days_remaining: int},
     *     spending_this_month: float,
     *     income_this_month: float,
     *     net_this_month: float,
     *     upcoming_bills: Collection<int, Bill>,
     *     overdue_bills: Collection<int, Bill>,
     *     total_debt: float,
     *     savings: array{total_saved: float, total_target: float, progress_percentage: float, active_goals: int, achieved_goals: int},
     *     available_cash: float,
     *     safe_to_spend: array{amount: float, days_remaining: int, per_day: float},
     *     recent_insights: Collection<int, AiInsight>,
     *     category_overspending: Collection<int, array{category: string, label: string, spent: float, target: float, over_by: float, percentage: float}>
     * }
     */
    public function summary(User $user): array
    {
        [$periodStart, $periodEnd] = $this->currentPeriod($user);
        $today = today();

        $spending = $this->spendingBetween($user, $periodStart, $periodEnd);
        $income = $this->incomeBetween($user, $periodStart, $periodEnd);
        $upcomingBills = $this->upcomingBills($user);
        $overdueBills = $this->overdueBills($user);

        $remainingBillsTotal = $upcomingBills
            ->whereBetween('next_due_on', [$today, $periodEnd])
            ->sum('amount');

        $availableCash = round($income - $spending - (float) $remainingBillsTotal, 2);
        $daysRemaining = max(0, (int) $today->diffInDays($periodEnd, false) + 1);

        return [
            'period' => [
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
                'days_remaining' => $daysRemaining,
            ],
            'spending_this_month' => round($spending, 2),
            'income_this_month' => round($income, 2),
            'net_this_month' => round($income - $spending, 2),
            'upcoming_bills' => $upcomingBills,
            'overdue_bills' => $overdueBills,
            'total_debt' => $this->totalDebt($user),
            'savings' => $this->savingsSummary($user),
            'available_cash' => $availableCash,
            'safe_to_spend' => $this->safeToSpend($availableCash, $daysRemaining),
            'recent_insights' => $this->recentInsights($user),
            'category_overspending' => $this->categoryOverspendingFlags($user, $periodStart, $periodEnd),
        ];
    }

    /**
     * Sum of expenses within the given inclusive date range.
     */
    public function spendingBetween(User $user, Carbon $from, Carbon $to): float
    {
        return (float) Expense::query()
            ->where('user_id', $user->id)
            ->between($from->toDateString(), $to->toDateString())
            ->sum('amount');
    }

    /**
     * Sum of logged income within the given inclusive date range.
     */
    public function incomeBetween(User $user, Carbon $from, Carbon $to): float
    {
        return (float) IncomeSource::query()
            ->where('user_id', $user->id)
            ->between($from->toDateString(), $to->toDateString())
            ->sum('amount');
    }

    /**
     * Active bills due within the upcoming window, ordered by next due date.
     *
     * @return Collection<int, Bill>
     */
    public function upcomingBills(User $user): Collection
    {
        return Bill::query()
            ->where('user_id', $user->id)
            ->active()
            ->dueWithin($this->upcomingWindowDays)
            ->orderBy('next_due_on')
            ->get();
    }

    /**
     * Active bills whose next due date is in the past.
     *
     * @return Collection<int, Bill>
     */
    public function overdueBills(User $user): Collection
    {
        return Bill::query()
            ->where('user_id', $user->id)
            ->active()
            ->whereDate('next_due_on', '<', today())
            ->orderBy('next_due_on')
            ->get();
    }

    /**
     * Sum of current balances across active debts.
     */
    public function totalDebt(User $user): float
    {
        return (float) Debt::query()
            ->where('user_id', $user->id)
            ->active()
            ->sum('balance');
    }

    /**
     * Aggregated savings goal progress.
     *
     * @return array{total_saved: float, total_target: float, progress_percentage: float, active_goals: int, achieved_goals: int}
     */
    public function savingsSummary(User $user): array
    {
        $goals = SavingsGoal::query()
            ->where('user_id', $user->id)
            ->get(['target_amount', 'current_amount', 'is_achieved']);

        $totalSaved = (float) $goals->sum('current_amount');
        $totalTarget = (float) $goals->sum('target_amount');
        $progress = $totalTarget > 0
            ? round(min(100, ($totalSaved / $totalTarget) * 100), 2)
            : 0.0;

        return [
            'total_saved' => round($totalSaved, 2),
            'total_target' => round($totalTarget, 2),
            'progress_percentage' => $progress,
            'active_goals' => (int) $goals->where('is_achieved', false)->count(),
            'achieved_goals' => (int) $goals->where('is_achieved', true)->count(),
        ];
    }

    /**
     * Split remaining cash evenly across the days left in the cycle.
     *
     * @return array{amount: float, days_remaining: int, per_day: float}
     */
    public function safeToSpend(float $availableCash, int $daysRemaining): array
    {
        $perDay = $daysRemaining > 0 ? round(max(0, $availableCash) / $daysRemaining, 2) : 0.0;

        return [
            'amount' => round($availableCash, 2),
            'days_remaining' => $daysRemaining,
            'per_day' => $perDay,
        ];
    }

    /**
     * Most recent unresolved insights.
     *
     * @return Collection<int, AiInsight>
     */
    public function recentInsights(User $user): Collection
    {
        return AiInsight::query()
            ->where('user_id', $user->id)
            ->new()
            ->latest()
            ->limit($this->recentInsightLimit)
            ->get();
    }

    /**
     * Categories whose spending has exceeded the configured budget target for the period.
     *
     * @return Collection<int, array{category: string, label: string, spent: float, target: float, over_by: float, percentage: float}>
     */
    public function categoryOverspendingFlags(User $user, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $targets = BudgetTarget::query()
            ->where('user_id', $user->id)
            ->forMonth($periodStart->year, $periodStart->month)
            ->get();

        if ($targets->isEmpty()) {
            return collect();
        }

        $spendByCategory = Expense::query()
            ->toBase()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_on', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return $targets
            ->map(function (BudgetTarget $target) use ($spendByCategory) {
                $categoryValue = $target->category?->value;
                $spent = (float) ($spendByCategory[$categoryValue] ?? 0);
                $targetAmount = (float) $target->amount;

                return [
                    'category' => (string) $categoryValue,
                    'label' => $target->category?->label() ?? 'Uncategorized',
                    'spent' => round($spent, 2),
                    'target' => round($targetAmount, 2),
                    'over_by' => round($spent - $targetAmount, 2),
                    'percentage' => $targetAmount > 0 ? round(($spent / $targetAmount) * 100, 2) : 0.0,
                ];
            })
            ->filter(fn (array $row): bool => $row['spent'] > $row['target'])
            ->sortByDesc('over_by')
            ->values();
    }

    /**
     * Compute the user's current budgeting cycle, respecting their configured start day.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function currentPeriod(User $user): array
    {
        $startDay = (int) ($user->financeSettings?->monthly_cycle_start_day ?? 1);
        $today = today();

        $start = $today->copy()->day(min($startDay, $today->daysInMonth));

        if ($today->day < $startDay) {
            $start->subMonthNoOverflow();
        }

        $end = $start->copy()->addMonthNoOverflow()->subDay();

        return [$start->startOfDay(), $end->endOfDay()];
    }
}
