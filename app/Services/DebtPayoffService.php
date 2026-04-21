<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Simulates debt payoff under the two classic strategies.
 *
 * Assumptions (manual-entry app, MVP-friendly):
 *  - APR compounds monthly: monthly rate = apr / 100 / 12.
 *  - Interest is accrued at the start of each month, then payments are applied.
 *  - Minimum payments are paid on every active debt; the remaining budget
 *    (user's extra_payment + minimums freed by already-paid-off debts) is
 *    funneled to the target debt — classic "debt snowball rollover".
 *  - Null / zero minimum_payment and null / zero apr are treated as 0 safely.
 *  - Simulation is capped at 600 months (50 years). Anything longer is
 *    reported as infeasible rather than looping forever.
 *  - We also flag infeasibility up-front if total budget cannot cover the
 *    first month of interest (the plan would grow, not shrink).
 */
class DebtPayoffService
{
    /** Safety cap on simulation length. */
    public const MAX_MONTHS = 600;

    /**
     * Plan a single strategy for the user.
     *
     * @return array{
     *     strategy: string,
     *     extra_payment: float,
     *     feasible: bool,
     *     months_to_payoff: ?int,
     *     debt_free_date: ?string,
     *     total_interest_paid: float,
     *     total_paid: float,
     *     payoff_order: Collection<int, array{id: int, name: string, starting_balance: float, apr: float, minimum_payment: float, paid_off_month: ?int, paid_off_date: ?string, interest_paid: float}>,
     *     target_debt: ?array{id: int, name: string, balance: float, apr: float},
     *     timeline: Collection<int, array{month: int, total_balance: float, interest: float, payment_applied: float}>
     * }
     */
    public function plan(User $user, string $strategy, float $extraPayment = 0.0): array
    {
        $debts = $user->debts()
            ->active()
            ->where('balance', '>', 0)
            ->get();

        return $this->simulate($debts, $strategy, $extraPayment);
    }

    /**
     * Run both strategies and return them side-by-side for comparison UI.
     *
     * @return array{snowball: array<string, mixed>, avalanche: array<string, mixed>}
     */
    public function compare(User $user, float $extraPayment = 0.0): array
    {
        return [
            'snowball' => $this->plan($user, 'snowball', $extraPayment),
            'avalanche' => $this->plan($user, 'avalanche', $extraPayment),
        ];
    }

    /**
     * The debt to focus extra payments on first under the given strategy.
     *
     * @param  Collection<int, Debt>  $debts
     * @return ?array{id: int, name: string, balance: float, apr: float}
     */
    public function targetDebt(Collection $debts, string $strategy): ?array
    {
        $first = $this->orderByStrategy($debts, $strategy)->first();

        if (! $first instanceof Debt) {
            return null;
        }

        return [
            'id' => (int) $first->id,
            'name' => (string) $first->name,
            'balance' => (float) $first->balance,
            'apr' => (float) ($first->apr ?? 0),
        ];
    }

    /**
     * Core simulation — walks the payoff month-by-month and returns the summary.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array<string, mixed>
     */
    public function simulate(Collection $debts, string $strategy, float $extraPayment): array
    {
        $extraPayment = max(0.0, $extraPayment);
        $ordered = $this->orderByStrategy($debts, $strategy);

        if ($ordered->isEmpty()) {
            return $this->emptyPlan($strategy, $extraPayment);
        }

        $state = $ordered->map(fn (Debt $d): array => [
            'id' => (int) $d->id,
            'name' => (string) $d->name,
            'starting_balance' => (float) $d->balance,
            'balance' => (float) $d->balance,
            'apr' => max(0.0, (float) ($d->apr ?? 0)),
            'minimum_payment' => max(0.0, (float) ($d->minimum_payment ?? 0)),
            'paid_off_month' => null,
            'interest_paid' => 0.0,
        ])->values()->all();

        if ($this->isInfeasible($state, $extraPayment)) {
            return $this->infeasiblePlan($strategy, $extraPayment, $state);
        }

        $timeline = collect();
        $totalInterest = 0.0;
        $totalPaid = 0.0;
        $month = 0;

        while ($month < self::MAX_MONTHS) {
            $month++;

            [$monthInterest, $monthPayment, $state] = $this->stepMonth($state, $extraPayment, $month);

            $totalInterest += $monthInterest;
            $totalPaid += $monthPayment;

            $remaining = $this->totalBalance($state);

            $timeline->push([
                'month' => $month,
                'total_balance' => round($remaining, 2),
                'interest' => round($monthInterest, 2),
                'payment_applied' => round($monthPayment, 2),
            ]);

            if ($remaining <= 0.01) {
                break;
            }
        }

        $feasible = $this->totalBalance($state) <= 0.01;
        $debtFreeDate = $feasible ? today()->copy()->addMonthsNoOverflow($month)->toDateString() : null;

        return [
            'strategy' => $strategy,
            'extra_payment' => round($extraPayment, 2),
            'feasible' => $feasible,
            'months_to_payoff' => $feasible ? $month : null,
            'debt_free_date' => $debtFreeDate,
            'total_interest_paid' => round($totalInterest, 2),
            'total_paid' => round($totalPaid, 2),
            'payoff_order' => $this->formatPayoffOrder($state),
            'target_debt' => $this->targetDebt($ordered, $strategy),
            'timeline' => $timeline,
        ];
    }

    /**
     * Order debts for the given strategy.
     *
     * @param  Collection<int, Debt>  $debts
     * @return Collection<int, Debt>
     */
    public function orderByStrategy(Collection $debts, string $strategy): Collection
    {
        return match ($strategy) {
            'snowball' => $debts->sortBy(fn (Debt $d): float => (float) $d->balance)->values(),
            'avalanche' => $debts->sortByDesc(fn (Debt $d): float => (float) ($d->apr ?? 0))->values(),
            default => $debts->values(),
        };
    }

    /**
     * Advance one month: accrue interest, pay minimums, then roll the remaining
     * budget (extras + freed minimums) to the first still-active debt.
     *
     * @param  array<int, array<string, mixed>>  $state
     * @return array{0: float, 1: float, 2: array<int, array<string, mixed>>}
     */
    private function stepMonth(array $state, float $extraPayment, int $month): array
    {
        $monthInterest = 0.0;
        $monthPayment = 0.0;

        foreach ($state as $i => $debt) {
            if ($debt['balance'] <= 0) {
                continue;
            }

            $interest = $debt['apr'] > 0 ? $debt['balance'] * $debt['apr'] / 100 / 12 : 0.0;
            $state[$i]['balance'] += $interest;
            $state[$i]['interest_paid'] += $interest;
            $monthInterest += $interest;
        }

        $freedMinimums = 0.0;

        foreach ($state as $i => $debt) {
            if ($debt['balance'] <= 0) {
                $freedMinimums += $debt['minimum_payment'];

                continue;
            }

            $payment = min($debt['minimum_payment'], $debt['balance']);
            $state[$i]['balance'] -= $payment;
            $monthPayment += $payment;
        }

        $budget = $extraPayment + $freedMinimums;

        foreach ($state as $i => $debt) {
            if ($budget <= 0) {
                break;
            }

            if ($debt['balance'] <= 0) {
                continue;
            }

            $payment = min($budget, $debt['balance']);
            $state[$i]['balance'] -= $payment;
            $budget -= $payment;
            $monthPayment += $payment;
        }

        foreach ($state as $i => $debt) {
            if ($debt['balance'] <= 0.01 && $debt['paid_off_month'] === null) {
                $state[$i]['balance'] = 0.0;
                $state[$i]['paid_off_month'] = $month;
            }
        }

        return [$monthInterest, $monthPayment, $state];
    }

    /**
     * Upfront feasibility check: does the total monthly budget at least cover
     * the first month's interest on all current balances?
     *
     * @param  array<int, array<string, mixed>>  $state
     */
    private function isInfeasible(array $state, float $extraPayment): bool
    {
        $monthInterest = 0.0;
        $monthBudget = $extraPayment;

        foreach ($state as $debt) {
            $monthInterest += $debt['apr'] > 0 ? $debt['balance'] * $debt['apr'] / 100 / 12 : 0.0;
            $monthBudget += $debt['minimum_payment'];
        }

        return $monthBudget < $monthInterest;
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return Collection<int, array<string, mixed>>
     */
    private function formatPayoffOrder(array $state): Collection
    {
        return collect($state)->map(fn (array $debt): array => [
            'id' => $debt['id'],
            'name' => $debt['name'],
            'starting_balance' => round($debt['starting_balance'], 2),
            'apr' => round($debt['apr'], 2),
            'minimum_payment' => round($debt['minimum_payment'], 2),
            'paid_off_month' => $debt['paid_off_month'],
            'paid_off_date' => $debt['paid_off_month']
                ? today()->copy()->addMonthsNoOverflow($debt['paid_off_month'])->toDateString()
                : null,
            'interest_paid' => round($debt['interest_paid'], 2),
        ])->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     */
    private function totalBalance(array $state): float
    {
        return (float) array_sum(array_column($state, 'balance'));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPlan(string $strategy, float $extraPayment): array
    {
        return [
            'strategy' => $strategy,
            'extra_payment' => round($extraPayment, 2),
            'feasible' => true,
            'months_to_payoff' => 0,
            'debt_free_date' => today()->toDateString(),
            'total_interest_paid' => 0.0,
            'total_paid' => 0.0,
            'payoff_order' => collect(),
            'target_debt' => null,
            'timeline' => collect(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return array<string, mixed>
     */
    private function infeasiblePlan(string $strategy, float $extraPayment, array $state): array
    {
        return [
            'strategy' => $strategy,
            'extra_payment' => round($extraPayment, 2),
            'feasible' => false,
            'months_to_payoff' => null,
            'debt_free_date' => null,
            'total_interest_paid' => 0.0,
            'total_paid' => 0.0,
            'payoff_order' => $this->formatPayoffOrder($state),
            'target_debt' => null,
            'timeline' => collect(),
        ];
    }
}
