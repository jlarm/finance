<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlannerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'strategy' => ['nullable', Rule::in(['snowball', 'avalanche'])],
            'extra_payment' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $settings = $request->user()->financeSettings;
        $strategy = $validated['strategy'] ?? $settings?->debt_strategy ?? 'avalanche';
        $extraPayment = (float) ($validated['extra_payment'] ?? 0);

        $debts = $request->user()->debts()
            ->active()
            ->where('balance', '>', 0)
            ->get();

        $ordered = match ($strategy) {
            'snowball' => $debts->sortBy('balance')->values(),
            'avalanche' => $debts->sortByDesc('apr')->values(),
        };

        return Inertia::render('debts/Planner', [
            'strategy' => $strategy,
            'extraPayment' => $extraPayment,
            'debts' => $ordered,
            'totals' => [
                'balance' => $debts->sum('balance'),
                'minimums' => $debts->sum('minimum_payment'),
            ],
        ]);
    }
}
