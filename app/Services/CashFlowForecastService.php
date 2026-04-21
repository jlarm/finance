<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Forward-looking cash-flow projector for a single user.
 *
 * Assumptions (manual-entry app, no bank integrations):
 *  - IncomeSource rows are individual received payments. Monthly income is
 *    estimated by averaging the trailing N completed months of logged income.
 *  - Bill recurrence is derived from the `frequency` field; custom bills use
 *    `interval_days`. We assume the next occurrence equals `next_due_on` and
 *    step forward from there, so `next_due_on` is expected to be current.
 *  - "Fixed obligations" = active bills normalized to a monthly equivalent
 *    (weekly ×52/12, biweekly ×26/12, quarterly /3, annual /12, custom uses
 *    30/interval_days as an approximation).
 *  - Weeks are ISO weeks (Monday–Sunday) via Carbon's startOfWeek/endOfWeek.
 *  - Available cash is derived from the calendar month: income logged so far
 *    minus expenses logged so far minus bills still due before month-end.
 */
class CashFlowForecastService
{
    public function __construct(
        private int $incomeLookbackMonths = 3,
        private int $forecastWeeks = 8,
        private float $clusterThresholdRatio = 0.5,
    ) {}

    /**
     * Build the complete forecast payload.
     *
     * @return array{
     *     monthly_income: float,
     *     monthly_fixed_obligations: float,
     *     monthly_discretionary: float,
     *     weekly_breakdown: Collection<int, array{week_start: string, week_end: string, bills: Collection<int, array{bill_id: int, name: string, amount: float, due_on: string}>, bills_total: float, projected_income: float, net: float}>,
     *     clustered_weeks: Collection<int, array{week_start: string, week_end: string, bills: Collection<int, array{bill_id: int, name: string, amount: float, due_on: string}>, bills_total: float, projected_income: float, net: float}>,
     *     tight_periods: Collection<int, array{week_start: string, week_end: string, bills: Collection<int, array{bill_id: int, name: string, amount: float, due_on: string}>, bills_total: float, projected_income: float, net: float}>,
     *     available_cash: float,
     *     safe_to_spend: array{amount: float, days_remaining: int, per_day: float}
     * }
     */
    public function forecast(User $user): array
    {
        $monthlyIncome = $this->estimateMonthlyIncome($user);
        $monthlyFixed = $this->estimateMonthlyFixedObligations($user);
        $weekly = $this->weeklyBreakdown($user, $monthlyIncome);

        return [
            'monthly_income' => $monthlyIncome,
            'monthly_fixed_obligations' => $monthlyFixed,
            'monthly_discretionary' => round(max(0, $monthlyIncome - $monthlyFixed), 2),
            'weekly_breakdown' => $weekly,
            'clustered_weeks' => $this->clusteredBillWeeks($weekly, $monthlyIncome),
            'tight_periods' => $this->tightCashFlowPeriods($weekly),
            'available_cash' => $this->remainingAvailableCash($user),
            'safe_to_spend' => $this->safeToSpend($user),
        ];
    }

    /**
     * Average monthly income across the trailing completed months.
     * Uses last `incomeLookbackMonths` fully-elapsed months (excludes the
     * current partial month so the number isn't artificially low).
     */
    public function estimateMonthlyIncome(User $user): float
    {
        $endOfLastMonth = today()->copy()->subMonthNoOverflow()->endOfMonth();
        $startOfWindow = $endOfLastMonth->copy()
            ->startOfMonth()
            ->subMonthsNoOverflow($this->incomeLookbackMonths - 1);

        $total = (float) IncomeSource::query()
            ->where('user_id', $user->id)
            ->between($startOfWindow->toDateString(), $endOfLastMonth->toDateString())
            ->sum('amount');

        return round($total / max(1, $this->incomeLookbackMonths), 2);
    }

    /**
     * Sum of active bills, each normalized to a monthly equivalent.
     */
    public function estimateMonthlyFixedObligations(User $user): float
    {
        $bills = Bill::query()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        return round((float) $bills->sum(fn (Bill $bill): float => $this->monthlyEquivalent($bill)), 2);
    }

    /**
     * Expand each active bill into its projected occurrences across the
     * forecast horizon. Bills are stepped forward from `next_due_on` by
     * frequency; custom bills use `interval_days`.
     *
     * @return Collection<int, array{bill_id: int, name: string, amount: float, due_on: string}>
     */
    public function projectBills(User $user): Collection
    {
        $horizon = today()->copy()->addWeeks($this->forecastWeeks)->endOfDay();
        $bills = Bill::query()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        $occurrences = collect();

        foreach ($bills as $bill) {
            $stepDays = $this->intervalDays($bill);
            $due = Carbon::parse($bill->next_due_on)->startOfDay();

            // Non-recurring (interval_days unknown): just include the single due date if it falls in-window.
            if ($stepDays <= 0) {
                if ($due->lte($horizon)) {
                    $occurrences->push($this->projectedOccurrence($bill, $due));
                }

                continue;
            }

            while ($due->lte($horizon)) {
                $occurrences->push($this->projectedOccurrence($bill, $due));
                $due = $due->copy()->addDays($stepDays);
            }
        }

        return $occurrences->sortBy('due_on')->values();
    }

    /**
     * Group projected bills into calendar weeks with income-vs-bills totals.
     *
     * @return Collection<int, array{week_start: string, week_end: string, bills: Collection<int, array{bill_id: int, name: string, amount: float, due_on: string}>, bills_total: float, projected_income: float, net: float}>
     */
    public function weeklyBreakdown(User $user, ?float $monthlyIncome = null): Collection
    {
        $monthlyIncome ??= $this->estimateMonthlyIncome($user);
        $weeklyIncome = round($monthlyIncome * 12 / 52, 2);
        $projected = $this->projectBills($user);

        $startOfWeek = today()->copy()->startOfWeek();

        return collect(range(0, $this->forecastWeeks - 1))
            ->map(function (int $offset) use ($startOfWeek, $projected, $weeklyIncome): array {
                $weekStart = $startOfWeek->copy()->addWeeks($offset);
                $weekEnd = $weekStart->copy()->endOfWeek();

                $bills = $projected->filter(
                    fn (array $o): bool => Carbon::parse($o['due_on'])->betweenIncluded($weekStart, $weekEnd)
                )->values();

                $billsTotal = round((float) $bills->sum('amount'), 2);

                return [
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                    'bills' => $bills,
                    'bills_total' => $billsTotal,
                    'projected_income' => $weeklyIncome,
                    'net' => round($weeklyIncome - $billsTotal, 2),
                ];
            });
    }

    /**
     * Weeks where projected bills exceed a share of average weekly income.
     * Default threshold: bills > 50% of weekly income.
     *
     * @param  Collection<int, array<string, mixed>>  $weeklyBreakdown
     * @return Collection<int, array<string, mixed>>
     */
    public function clusteredBillWeeks(Collection $weeklyBreakdown, float $monthlyIncome): Collection
    {
        $threshold = max(0.0, ($monthlyIncome * 12 / 52) * $this->clusterThresholdRatio);

        return $weeklyBreakdown
            ->filter(fn (array $week): bool => $week['bills_total'] > $threshold && $week['bills']->isNotEmpty())
            ->values();
    }

    /**
     * Weeks where projected bills exceed projected income (negative net).
     *
     * @param  Collection<int, array<string, mixed>>  $weeklyBreakdown
     * @return Collection<int, array<string, mixed>>
     */
    public function tightCashFlowPeriods(Collection $weeklyBreakdown): Collection
    {
        return $weeklyBreakdown
            ->filter(fn (array $week): bool => $week['net'] < 0)
            ->values();
    }

    /**
     * Cash left for the rest of the current calendar month:
     * income logged this month minus expenses logged this month minus
     * bills still due before month-end.
     */
    public function remainingAvailableCash(User $user): float
    {
        $today = today();
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();

        $income = (float) IncomeSource::query()
            ->where('user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $spent = (float) Expense::query()
            ->where('user_id', $user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $billsRemaining = (float) Bill::query()
            ->where('user_id', $user->id)
            ->active()
            ->whereBetween('next_due_on', [$today->toDateString(), $end->toDateString()])
            ->sum('amount');

        return round($income - $spent - $billsRemaining, 2);
    }

    /**
     * Available cash split evenly across the days remaining in the month.
     *
     * @return array{amount: float, days_remaining: int, per_day: float}
     */
    public function safeToSpend(User $user): array
    {
        $today = today();
        $end = $today->copy()->endOfMonth();
        $daysRemaining = max(1, (int) $today->diffInDays($end, false) + 1);
        $available = $this->remainingAvailableCash($user);

        return [
            'amount' => $available,
            'days_remaining' => $daysRemaining,
            'per_day' => round(max(0.0, $available) / $daysRemaining, 2),
        ];
    }

    /**
     * Normalize a bill's amount to a per-month figure based on its frequency.
     */
    private function monthlyEquivalent(Bill $bill): float
    {
        $amount = (float) $bill->amount;

        return match ($bill->frequency) {
            'weekly' => $amount * 52 / 12,
            'biweekly' => $amount * 26 / 12,
            'monthly' => $amount,
            'quarterly' => $amount / 3,
            'annual' => $amount / 12,
            'custom' => $bill->interval_days > 0 ? $amount * 30 / (float) $bill->interval_days : $amount,
            default => $amount,
        };
    }

    /**
     * Step size in days between recurrences of a bill.
     * Monthly is approximated as 30 days for projection purposes.
     */
    private function intervalDays(Bill $bill): int
    {
        return match ($bill->frequency) {
            'weekly' => 7,
            'biweekly' => 14,
            'monthly' => 30,
            'quarterly' => 91,
            'annual' => 365,
            'custom' => (int) ($bill->interval_days ?? 0),
            default => 0,
        };
    }

    /**
     * @return array{bill_id: int, name: string, amount: float, due_on: string}
     */
    private function projectedOccurrence(Bill $bill, Carbon $due): array
    {
        return [
            'bill_id' => (int) $bill->id,
            'name' => (string) $bill->name,
            'amount' => (float) $bill->amount,
            'due_on' => $due->toDateString(),
        ];
    }
}
