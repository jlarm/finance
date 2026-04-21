<?php

namespace App\Services\AI;

use App\Models\Bill;
use App\Models\BudgetTarget;
use App\Models\Expense;
use App\Models\IncomeSource;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\CashFlowForecastService;
use App\Services\DashboardService;
use App\Services\DebtPayoffService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds prompt-ready context arrays from user-entered finance data.
 *
 * This is the ONLY place that decides what data ever reaches the LLM.
 * It selects explicit columns (whitelist, not blacklist) so free-text
 * fields like notes/descriptions are never included.
 */
class FinancialDataSummaryBuilder
{
    public function __construct(
        private DashboardService $dashboard,
        private CashFlowForecastService $cashflow,
        private DebtPayoffService $payoff,
        private int $topCategoryLimit = 6,
        private int $upcomingBillLimit = 10,
    ) {}

    /**
     * Context for MonthlySummaryPrompt.
     *
     * @return array<string, mixed>
     */
    public function forMonthly(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $start->copy()->subMonthNoOverflow()->endOfMonth();

        $income = (float) IncomeSource::query()
            ->where('user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $spending = (float) Expense::query()
            ->where('user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $previousIncome = (float) IncomeSource::query()
            ->where('user_id', $user->id)
            ->between($previousStart->toDateString(), $previousEnd->toDateString())
            ->sum('amount');

        $previousSpending = (float) Expense::query()
            ->where('user_id', $user->id)
            ->between($previousStart->toDateString(), $previousEnd->toDateString())
            ->sum('amount');

        $billsPaid = (int) Bill::query()
            ->where('user_id', $user->id)
            ->whereBetween('last_paid_on', [$start->toDateString(), $end->toDateString()])
            ->count();

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'month' => $start->format('Y-m'),
            'totals' => [
                'income' => round($income, 2),
                'spending' => round($spending, 2),
                'net' => round($income - $spending, 2),
            ],
            'vs_previous_month' => [
                'income_delta' => round($income - $previousIncome, 2),
                'spending_delta' => round($spending - $previousSpending, 2),
            ],
            'top_categories' => $this->topCategoriesForPeriod($user, $start, $end),
            'bills_paid' => $billsPaid,
        ];
    }

    /**
     * Context for SpendingPatternPrompt.
     *
     * @return array<string, mixed>
     */
    public function forSpendingPattern(User $user): array
    {
        [$start, $end] = $this->dashboard->currentPeriod($user);
        $days = $start->diffInDays($end) + 1;

        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subDay();

        $current = $this->categoryTotals($user, $start, $end);
        $previous = $this->categoryTotals($user, $previousStart, $previousEnd);
        $targets = $this->budgetTargetsForPeriod($user, $start);

        $categories = $current
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'spent' => $row['spent'],
                'target' => $targets[$row['category_id']] ?? null,
                'prev_period_spent' => $previous[$row['category_id']]['spent'] ?? 0.0,
            ])
            ->sortByDesc('spent')
            ->take($this->topCategoryLimit)
            ->values()
            ->all();

        $transactionCounts = Expense::query()
            ->where('user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->selectRaw('expense_category_id, COUNT(*) as c')
            ->groupBy('expense_category_id')
            ->pluck('c', 'expense_category_id')
            ->all();

        $countsByName = collect($categories)
            ->mapWithKeys(fn (array $row): array => [
                $row['name'] => (int) ($transactionCounts[$this->categoryIdByName($user, $row['name'])] ?? 0),
            ])
            ->all();

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'categories' => $categories,
            'transaction_counts' => $countsByName,
        ];
    }

    /**
     * Context for CashFlowRiskPrompt.
     *
     * @return array<string, mixed>
     */
    public function forCashFlowRisk(User $user): array
    {
        $forecast = $this->cashflow->forecast($user);

        $tightWeeks = $forecast['tight_periods']
            ->take(1)
            ->map(fn (array $w): array => [
                'week_start' => $w['week_start'],
                'week_end' => $w['week_end'],
                'bills_total' => $w['bills_total'],
                'projected_income' => $w['projected_income'],
                'net' => $w['net'],
                'bills' => $w['bills']->all(),
            ])
            ->values()
            ->all();

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'monthly_income_estimate' => $forecast['monthly_income'],
            'monthly_fixed_obligations' => $forecast['monthly_fixed_obligations'],
            'tight_weeks' => $tightWeeks,
        ];
    }

    /**
     * Context for DebtCoachingPrompt.
     *
     * @return array<string, mixed>
     */
    public function forDebtCoaching(User $user, float $extraPayment = 0.0): array
    {
        $strategy = $user->financeSettings?->debt_strategy ?? 'avalanche';
        $plan = $this->payoff->plan($user, $strategy, $extraPayment);
        $totalDebt = $this->dashboard->totalDebt($user);

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'strategy' => $plan['strategy'],
            'extra_payment' => $plan['extra_payment'],
            'feasible' => $plan['feasible'],
            'months_to_payoff' => $plan['months_to_payoff'],
            'debt_free_date' => $plan['debt_free_date'],
            'total_interest_paid' => $plan['total_interest_paid'],
            'target_debt' => $plan['target_debt'],
            'debt_count' => $plan['payoff_order']->count(),
            'total_debt' => $totalDebt,
        ];
    }

    /**
     * Context for SavingsPacingPrompt.
     *
     * @return array<string, mixed>
     */
    public function forSavingsPacing(User $user): array
    {
        $today = today();

        $goals = SavingsGoal::query()
            ->where('user_id', $user->id)
            ->active()
            ->get(['id', 'name', 'current_amount', 'target_amount', 'target_date', 'created_at'])
            ->map(function (SavingsGoal $goal) use ($today): array {
                $current = (float) $goal->current_amount;
                $target = (float) $goal->target_amount;
                $monthsSinceStart = max(1, (int) $goal->created_at->diffInMonths($today) + 1);
                $avgContribution = round($current / $monthsSinceStart, 2);

                $projected = null;
                $needsAttention = false;

                if ($goal->target_date instanceof Carbon) {
                    $monthsRemaining = max(0, (int) $today->diffInMonths($goal->target_date, false));
                    $projected = round($current + ($avgContribution * $monthsRemaining), 2);
                    $needsAttention = $projected < $target;
                }

                return [
                    'name' => (string) $goal->name,
                    'current_amount' => round($current, 2),
                    'target_amount' => round($target, 2),
                    'target_date' => $goal->target_date?->toDateString(),
                    'avg_monthly_contribution' => $avgContribution,
                    'projected_amount_at_target_date' => $projected,
                    'needs_attention' => $needsAttention,
                ];
            })
            ->values()
            ->all();

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'today' => $today->toDateString(),
            'goals' => $goals,
        ];
    }

    private function tone(User $user): string
    {
        return $user->financeSettings?->ai_tone ?? 'supportive';
    }

    private function currency(User $user): string
    {
        return $user->financeSettings?->currency ?? 'USD';
    }

    /**
     * @return array<int, array{category_id: int, name: string, spent: float, target: ?float}>
     */
    private function topCategoriesForPeriod(User $user, Carbon $start, Carbon $end): array
    {
        $targets = $this->budgetTargetsForPeriod($user, $start);

        return $this->categoryTotals($user, $start, $end)
            ->map(fn (array $row): array => [
                'category_id' => $row['category_id'],
                'name' => $row['name'],
                'spent' => $row['spent'],
                'target' => $targets[$row['category_id']] ?? null,
            ])
            ->sortByDesc('spent')
            ->take($this->topCategoryLimit)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{category_id: int, name: string, spent: float}>
     */
    private function categoryTotals(User $user, Carbon $start, Carbon $end): Collection
    {
        return Expense::query()
            ->where('expenses.user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->groupBy('expenses.expense_category_id', 'expense_categories.name')
            ->selectRaw(
                'expenses.expense_category_id as category_id, expense_categories.name as name, SUM(expenses.amount) as total'
            )
            ->get()
            ->map(fn ($row): array => [
                'category_id' => (int) $row->category_id,
                'name' => (string) $row->name,
                'spent' => round((float) $row->total, 2),
            ]);
    }

    /**
     * @return array<int, float> [expense_category_id => target amount]
     */
    private function budgetTargetsForPeriod(User $user, Carbon $start): array
    {
        return BudgetTarget::query()
            ->where('user_id', $user->id)
            ->forMonth($start->year, $start->month)
            ->pluck('amount', 'expense_category_id')
            ->map(fn ($amount): float => round((float) $amount, 2))
            ->all();
    }

    private function categoryIdByName(User $user, string $name): ?int
    {
        return $user->expenseCategories()
            ->where('name', $name)
            ->value('id');
    }
}
