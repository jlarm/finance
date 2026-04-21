<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\UpdateDebtRequest;
use App\Models\Debt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    public function index(Request $request): Response
    {
        $debts = $request->user()->debts()
            ->active()
            ->orderByDesc('balance')
            ->get();

        return Inertia::render('debts/Index', [
            'debts' => $debts,
            'totals' => [
                'balance' => $debts->sum('balance'),
                'minimums' => $debts->sum('minimum_payment'),
            ],
        ]);
    }

    public function show(Request $request, Debt $debt): Response
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        return Inertia::render('debts/Show', [
            'debt' => $debt,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('debts/Create');
    }

    public function store(StoreDebtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['original_balance'] ??= $data['balance'];

        $request->user()->debts()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Debt added.')]);

        return to_route('debts.index');
    }

    public function edit(Request $request, Debt $debt): Response
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        return Inertia::render('debts/Edit', [
            'debt' => $debt,
        ]);
    }

    public function update(UpdateDebtRequest $request, Debt $debt): RedirectResponse
    {
        $debt->update($request->validated());

        if ((float) $debt->balance === 0.0 && $debt->is_active) {
            $debt->update(['is_active' => false]);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Debt paid off — nice work.')]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Debt updated.')]);
        }

        return to_route('debts.index');
    }

    public function destroy(Request $request, Debt $debt): RedirectResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        $debt->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Debt removed.')]);

        return to_route('debts.index');
    }
}
