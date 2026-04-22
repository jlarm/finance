<?php

namespace App\Services\AI;

use App\Enums\ExpenseCategory;
use App\Models\Bill;
use App\Models\BudgetTarget;
use App\Models\Expense;
use App\Models\IncomeSource;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\CashFlowForecastService;
use App\Services\DashboardService;
use App\Services\DebtPayoffService;
use Carbon\CarbonInterface;
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
    public function forMonthly(User $user, CarbonInterface $month): array
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

        $previousByCategory = $previous->keyBy('category');

        $categories = $current
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'spent' => $row['spent'],
                'target' => $targets[$row['category']] ?? null,
                'prev_period_spent' => (float) ($previousByCategory[$row['category']]['spent'] ?? 0.0),
            ])
            ->sortByDesc('spent')
            ->take($this->topCategoryLimit)
            ->values()
            ->all();

        $transactionCounts = Expense::query()
            ->toBase()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category, COUNT(*) as c')
            ->groupBy('category')
            ->pluck('c', 'category')
            ->all();

        $countsByName = [];
        foreach ($current as $row) {
            $countsByName[$row['name']] = (int) ($transactionCounts[$row['category']] ?? 0);
        }

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

                if ($goal->target_date instanceof CarbonInterface) {
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

    /**
     * Context for AssistantAffordabilityPrompt (chat).
     *
     * @return array<string, mixed>
     */
    public function forAffordability(User $user, string $question): array
    {
        $dashboard = $this->dashboard->summary($user);

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'question' => $question,
            'available_cash' => $dashboard['available_cash'],
            'safe_to_spend' => $dashboard['safe_to_spend'],
            'upcoming_bills_total' => round(
                (float) $dashboard['upcoming_bills']->sum('amount'),
                2,
            ),
            'top_categories' => $this->topCategoriesForPeriod(
                $user,
                Carbon::parse($dashboard['period']['start']),
                Carbon::parse($dashboard['period']['end']),
            ),
        ];
    }

    /**
     * Context for AssistantGeneralPrompt (chat).
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<string, mixed>
     */
    public function forChat(User $user, string $question, array $history = []): array
    {
        $thisMonthStart = today()->copy()->startOfMonth();
        $thisMonthEnd = today()->copy()->endOfMonth();
        $lastMonthStart = $thisMonthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $thisMonthStart->copy()->subMonthNoOverflow()->endOfMonth();

        return [
            'tone' => $this->tone($user),
            'currency' => $this->currency($user),
            'question' => $question,
            'history' => $history,
            'summary' => [
                'current_month' => [
                    'income' => round(
                        (float) IncomeSource::query()
                            ->where('user_id', $user->id)
                            ->between($thisMonthStart->toDateString(), $thisMonthEnd->toDateString())
                            ->sum('amount'),
                        2,
                    ),
                    'spending' => round(
                        (float) Expense::query()
                            ->where('user_id', $user->id)
                            ->between($thisMonthStart->toDateString(), $thisMonthEnd->toDateString())
                            ->sum('amount'),
                        2,
                    ),
                ],
                'last_month' => [
                    'income' => round(
                        (float) IncomeSource::query()
                            ->where('user_id', $user->id)
                            ->between($lastMonthStart->toDateString(), $lastMonthEnd->toDateString())
                            ->sum('amount'),
                        2,
                    ),
                    'spending' => round(
                        (float) Expense::query()
                            ->where('user_id', $user->id)
                            ->between($lastMonthStart->toDateString(), $lastMonthEnd->toDateString())
                            ->sum('amount'),
                        2,
                    ),
                ],
                'categories_last_month' => $this->topCategoriesForPeriod($user, $lastMonthStart, $lastMonthEnd),
                'debts' => $user->debts()
                    ->active()
                    ->get(['name', 'balance', 'apr', 'minimum_payment'])
                    ->map(fn ($d): array => [
                        'name' => (string) $d->name,
                        'balance' => round((float) $d->balance, 2),
                        'apr' => round((float) ($d->apr ?? 0), 2),
                        'minimum_payment' => round((float) ($d->minimum_payment ?? 0), 2),
                    ])
                    ->all(),
                'goals' => $user->savingsGoals()
                    ->active()
                    ->get(['name', 'current_amount', 'target_amount', 'target_date'])
                    ->map(fn ($g): array => [
                        'name' => (string) $g->name,
                        'current_amount' => round((float) $g->current_amount, 2),
                        'target_amount' => round((float) $g->target_amount, 2),
                        'target_date' => $g->target_date?->toDateString(),
                    ])
                    ->all(),
                'upcoming_bills' => $this->dashboard->upcomingBills($user)
                    ->take($this->upcomingBillLimit)
                    ->map(fn ($b): array => [
                        'name' => (string) $b->name,
                        'amount' => round((float) $b->amount, 2),
                        'due_on' => $b->next_due_on?->toDateString(),
                    ])
                    ->values()
                    ->all(),
            ],
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
     * @return array<int, array{category: string, name: string, spent: float, target: ?float}>
     */
    private function topCategoriesForPeriod(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $targets = $this->budgetTargetsForPeriod($user, $start);

        return $this->categoryTotals($user, $start, $end)
            ->map(fn (array $row): array => [
                'category' => $row['category'],
                'name' => $row['name'],
                'spent' => $row['spent'],
                'target' => $targets[$row['category']] ?? null,
            ])
            ->sortByDesc('spent')
            ->take($this->topCategoryLimit)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{category: string, name: string, spent: float}>
     */
    private function categoryTotals(User $user, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Expense::query()
            ->toBase()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row): array => [
                'category' => (string) $row->category,
                'name' => ExpenseCategory::tryFrom((string) $row->category)?->label() ?? 'Uncategorized',
                'spent' => round((float) $row->total, 2),
            ]);
    }

    /**
     * @return array<string, float> [category enum value => target amount]
     */
    private function budgetTargetsForPeriod(User $user, CarbonInterface $start): array
    {
        return BudgetTarget::query()
            ->toBase()
            ->where('user_id', $user->id)
            ->whereYear('period_month', $start->year)
            ->whereMonth('period_month', $start->month)
            ->pluck('amount', 'category')
            ->map(fn ($amount): float => round((float) $amount, 2))
            ->all();
    }
}
