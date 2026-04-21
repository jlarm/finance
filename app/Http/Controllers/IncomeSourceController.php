<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeSourceRequest;
use App\Http\Requests\UpdateIncomeSourceRequest;
use App\Models\IncomeSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $incomes = $request->user()->incomeSources()
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('received_on', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('received_on', '<=', $d))
            ->orderByDesc('received_on')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('income-sources/Index', [
            'incomes' => $incomes,
            'filters' => $filters,
        ]);
    }

    public function store(StoreIncomeSourceRequest $request): RedirectResponse
    {
        $request->user()->incomeSources()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income added.')]);

        return to_route('income-sources.index');
    }

    public function update(UpdateIncomeSourceRequest $request, IncomeSource $incomeSource): RedirectResponse
    {
        $incomeSource->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income updated.')]);

        return to_route('income-sources.index');
    }

    public function destroy(Request $request, IncomeSource $incomeSource): RedirectResponse
    {
        abort_if($incomeSource->user_id !== $request->user()->id, 403);

        $incomeSource->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income removed.')]);

        return to_route('income-sources.index');
    }
}
