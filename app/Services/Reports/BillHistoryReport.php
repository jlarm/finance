<?php

namespace App\Services\Reports;

use App\Models\Bill;
use App\Models\User;

/**
 * Bill-by-bill view centered on decision-making: what's due soon,
 * how long since it was last paid, and rough monthly cost.
 *
 * The app does not store a separate payment history — we rely on
 * last_paid_on and the bill's frequency. If richer history is added
 * later (payment ledger table), extend this service, not the page.
 */
class BillHistoryReport
{
    /**
     * @return array{
     *     bills: array<int, array{
     *         id: int,
     *         name: string,
     *         amount: float,
     *         frequency: string,
     *         interval_days: ?int,
     *         next_due_on: ?string,
     *         last_paid_on: ?string,
     *         days_until_due: ?int,
     *         days_since_paid: ?int,
     *         estimated_monthly_cost: float,
     *         is_active: bool
     *     }>,
     *     totals: array{estimated_monthly: float, active_count: int}
     * }
     */
    public function build(User $user): array
    {
        $bills = $user->bills()
            ->orderBy('is_active', 'desc')
            ->orderBy('next_due_on')
            ->get();

        $today = today();

        $items = $bills->map(function (Bill $bill) use ($today): array {
            $nextDue = $bill->next_due_on;
            $lastPaid = $bill->last_paid_on;

            return [
                'id' => (int) $bill->id,
                'name' => (string) $bill->name,
                'amount' => round((float) $bill->amount, 2),
                'frequency' => (string) $bill->frequency,
                'interval_days' => $bill->interval_days !== null ? (int) $bill->interval_days : null,
                'next_due_on' => $nextDue?->toDateString(),
                'last_paid_on' => $lastPaid?->toDateString(),
                'days_until_due' => $nextDue !== null ? $today->diffInDays($nextDue, false) : null,
                'days_since_paid' => $lastPaid !== null ? $lastPaid->diffInDays($today, false) : null,
                'estimated_monthly_cost' => $this->monthlyCost($bill),
                'is_active' => (bool) $bill->is_active,
            ];
        })->all();

        $activeMonthly = array_sum(array_map(
            fn (array $item): float => $item['is_active'] ? $item['estimated_monthly_cost'] : 0.0,
            $items,
        ));

        $activeCount = count(array_filter($items, fn (array $item): bool => $item['is_active']));

        return [
            'bills' => $items,
            'totals' => [
                'estimated_monthly' => round($activeMonthly, 2),
                'active_count' => $activeCount,
            ],
        ];
    }

    /**
     * Normalize a bill's amount to a per-month figure. Mirrors the logic
     * in CashFlowForecastService so numbers stay consistent across pages.
     */
    private function monthlyCost(Bill $bill): float
    {
        $amount = (float) $bill->amount;

        $monthly = match ($bill->frequency) {
            'weekly' => $amount * 52 / 12,
            'biweekly' => $amount * 26 / 12,
            'monthly' => $amount,
            'quarterly' => $amount / 3,
            'annual' => $amount / 12,
            'custom' => $bill->interval_days > 0 ? $amount * (30.0 / $bill->interval_days) : $amount,
            default => $amount,
        };

        return round($monthly, 2);
    }
}
