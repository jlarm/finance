<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $expenses = $request->user()->expenses()
            ->with('category')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('occurred_on', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('occurred_on', '<=', $d))
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('expense_category_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('expenses/Index', [
            'expenses' => $expenses,
            'categories' => $request->user()->expenseCategories()->active()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('expenses/Create', [
            'categories' => $request->user()->expenseCategories()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $request->user()->expenses()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense added.')]);

        return to_route('expenses.index');
    }

    public function edit(Request $request, Expense $expense): Response
    {
        abort_if($expense->user_id !== $request->user()->id, 403);

        return Inertia::render('expenses/Edit', [
            'expense' => $expense,
            'categories' => $request->user()->expenseCategories()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense updated.')]);

        return to_route('expenses.index');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        abort_if($expense->user_id !== $request->user()->id, 403);

        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense removed.')]);

        return to_route('expenses.index');
    }
}
