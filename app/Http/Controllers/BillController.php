<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    public function index(Request $request): Response
    {
        $bills = $request->user()->bills()
            ->with('category')
            ->active()
            ->orderBy('next_due_on')
            ->get();

        return Inertia::render('bills/Index', [
            'bills' => $bills,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('bills/Create', [
            'categories' => $request->user()->expenseCategories()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBillRequest $request): RedirectResponse
    {
        $request->user()->bills()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill added.')]);

        return to_route('bills.index');
    }

    public function edit(Request $request, Bill $bill): Response
    {
        abort_if($bill->user_id !== $request->user()->id, 403);

        return Inertia::render('bills/Edit', [
            'bill' => $bill,
            'categories' => $request->user()->expenseCategories()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateBillRequest $request, Bill $bill): RedirectResponse
    {
        $bill->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill updated.')]);

        return to_route('bills.index');
    }

    public function destroy(Request $request, Bill $bill): RedirectResponse
    {
        abort_if($bill->user_id !== $request->user()->id, 403);

        $bill->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill removed.')]);

        return to_route('bills.index');
    }
}
