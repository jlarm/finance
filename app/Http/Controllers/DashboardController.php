<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $expensesThisMonth = $user->expenses()
            ->whereBetween('occurred_on', [$monthStart, $monthEnd])
            ->sum('amount');

        $incomeThisMonth = $user->incomeSources()
            ->whereBetween('received_on', [$monthStart, $monthEnd])
            ->sum('amount');

        $totalDebt = $user->debts()->active()->sum('balance');
        $totalSavings = $user->savingsGoals()->sum('current_amount');

        $upcomingBills = $user->bills()
            ->active()
            ->orderBy('next_due_on')
            ->limit(5)
            ->get();

        $activeGoals = $user->savingsGoals()
            ->active()
            ->orderBy('target_date')
            ->limit(3)
            ->get();

        $recentInsights = $user->aiInsights()
            ->new()
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'summary' => [
                'expenses_this_month' => $expensesThisMonth,
                'income_this_month' => $incomeThisMonth,
                'net_this_month' => (float) $incomeThisMonth - (float) $expensesThisMonth,
                'total_debt' => $totalDebt,
                'total_savings' => $totalSavings,
                'net_worth' => (float) $totalSavings - (float) $totalDebt,
            ],
            'upcomingBills' => $upcomingBills,
            'activeGoals' => $activeGoals,
            'recentInsights' => $recentInsights,
        ]);
    }
}
