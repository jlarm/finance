<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetTargetRequest;
use App\Http\Requests\UpdateBudgetTargetRequest;
use App\Models\BudgetTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetTargetController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $periodMonth = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();

        $user = $request->user();

        $targets = $user->budgetTargets()
            ->whereDate('period_month', $periodMonth)
            ->get();

        $actuals = $user->expenses()
            ->toBase()
            ->whereBetween('occurred_on', [$periodMonth, $periodMonth->copy()->endOfMonth()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return Inertia::render('budget-targets/Index', [
            'periodMonth' => $periodMonth->toDateString(),
            'targets' => $targets,
            'actuals' => $actuals,
        ]);
    }

    public function store(StoreBudgetTargetRequest $request): RedirectResponse
    {
        $request->user()->budgetTargets()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget target saved.')]);

        return back();
    }

    public function update(UpdateBudgetTargetRequest $request, BudgetTarget $budgetTarget): RedirectResponse
    {
        $budgetTarget->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget target updated.')]);

        return back();
    }

    public function destroy(Request $request, BudgetTarget $budgetTarget): RedirectResponse
    {
        abort_if($budgetTarget->user_id !== $request->user()->id, 403);

        $budgetTarget->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget target removed.')]);

        return back();
    }
}
