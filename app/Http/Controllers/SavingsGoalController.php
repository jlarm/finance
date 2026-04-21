<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Models\SavingsGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    public function index(Request $request): Response
    {
        $goals = $request->user()->savingsGoals()
            ->orderBy('is_achieved')
            ->orderBy('target_date')
            ->get();

        return Inertia::render('savings-goals/Index', [
            'goals' => $goals,
        ]);
    }

    public function store(StoreSavingsGoalRequest $request): RedirectResponse
    {
        $request->user()->savingsGoals()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings goal created.')]);

        return to_route('savings-goals.index');
    }

    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['current_amount']) && (float) $data['current_amount'] >= (float) $savingsGoal->target_amount) {
            $data['is_achieved'] = true;
        }

        $savingsGoal->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goal updated.')]);

        return to_route('savings-goals.index');
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        abort_if($savingsGoal->user_id !== $request->user()->id, 403);

        $savingsGoal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goal removed.')]);

        return to_route('savings-goals.index');
    }
}
