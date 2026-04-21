<?php

namespace App\Http\Controllers;

use App\Services\Reports\BillHistoryReport;
use App\Services\Reports\CashFlowForecastReport;
use App\Services\Reports\CategoryTrendsReport;
use App\Services\Reports\DebtProgressReport;
use App\Services\Reports\MonthlySpendingReport;
use App\Services\Reports\SavingsProgressReport;
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

    public function spending(Request $request, MonthlySpendingReport $report): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        return Inertia::render('reports/Spending', $report->build($request->user(), $from, $to));
    }

    public function categories(Request $request, CategoryTrendsReport $report): Response
    {
        $validated = $request->validate([
            'months' => ['nullable', 'integer', 'min:2', 'max:24'],
        ]);

        return Inertia::render('reports/Categories', $report->build(
            $request->user(),
            $validated['months'] ?? 6,
        ));
    }

    public function bills(Request $request, BillHistoryReport $report): Response
    {
        return Inertia::render('reports/Bills', $report->build($request->user()));
    }

    public function debts(Request $request, DebtProgressReport $report): Response
    {
        return Inertia::render('reports/Debts', $report->build($request->user()));
    }

    public function savings(Request $request, SavingsProgressReport $report): Response
    {
        return Inertia::render('reports/Savings', $report->build($request->user()));
    }

    public function cashFlow(Request $request, CashFlowForecastReport $report): Response
    {
        return Inertia::render('reports/CashFlow', $report->build($request->user()));
    }
}
