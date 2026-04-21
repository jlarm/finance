<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reports/Index');
    }

    public function spending(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : now()->subMonths(5)->startOfMonth();
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now()->endOfMonth();

        $user = $request->user();

        $byCategory = $user->expenses()
            ->with('category:id,name,color')
            ->whereBetween('occurred_on', [$from, $to])
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get();

        $byMonth = $user->expenses()
            ->whereBetween('occurred_on', [$from, $to])
            ->selectRaw("DATE_FORMAT(occurred_on, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('reports/Spending', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'byCategory' => $byCategory,
            'byMonth' => $byMonth,
        ]);
    }

    public function cashFlow(Request $request): Response
    {
        $validated = $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $months = $validated['months'] ?? 6;
        $from = now()->subMonths($months - 1)->startOfMonth();
        $to = now()->endOfMonth();

        $user = $request->user();

        $income = $user->incomeSources()
            ->whereBetween('received_on', [$from, $to])
            ->selectRaw("DATE_FORMAT(received_on, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $expenses = $user->expenses()
            ->whereBetween('occurred_on', [$from, $to])
            ->selectRaw("DATE_FORMAT(occurred_on, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return Inertia::render('reports/CashFlow', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'income' => $income,
            'expenses' => $expenses,
        ]);
    }
}
