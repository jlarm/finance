<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    public function index(Request $request): Response
    {
        $bills = $request->user()->bills()
            ->with('debt')
            ->active()
            ->orderBy('next_due_on')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('bills/Index', [
            'bills' => $bills,
            'debts' => $request->user()->debts()->active()->orderBy('name')->get(['id', 'name', 'balance']),
        ]);
    }

    public function store(StoreBillRequest $request): RedirectResponse
    {
        $request->user()->bills()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill added.')]);

        return to_route('bills.index');
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

    public function pay(Request $request, Bill $bill): RedirectResponse
    {
        abort_if($bill->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
        ]);

        $paidAmount = isset($validated['amount'])
            ? (float) $validated['amount']
            : ($bill->split_across_paychecks
                ? round((float) $bill->amount / 2, 2)
                : (float) $bill->amount);

        DB::transaction(function () use ($bill, $paidAmount): void {
            $today = today();

            $bill->forceFill([
                'last_paid_on' => $today,
                'next_due_on' => $this->advanceDueDate($bill, $today),
            ])->save();

            if ($bill->debt_id !== null) {
                $debt = $bill->debt()->lockForUpdate()->first();

                if ($debt !== null) {
                    $newBalance = max(0, (float) $debt->balance - $paidAmount);
                    $debt->forceFill(['balance' => $newBalance])->save();
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded.')]);

        return to_route('bills.index');
    }

    private function advanceDueDate(Bill $bill, CarbonInterface $from): CarbonInterface
    {
        $anchor = Date::parse($bill->next_due_on)->max($from);

        // Split bills are paid in two parts across two paychecks, so each
        // "Mark paid" advances by roughly half a cycle. Two payments then
        // cycle the bill forward by ~one full period.
        if ($bill->split_across_paychecks) {
            return $anchor->addDays(14);
        }

        return match ($bill->frequency) {
            'weekly' => $anchor->addWeek(),
            'biweekly' => $anchor->addWeeks(2),
            'monthly' => $anchor->addMonthNoOverflow(),
            'quarterly' => $anchor->addMonthsNoOverflow(3),
            'annual' => $anchor->addYearNoOverflow(),
            'custom' => $anchor->addDays(max(1, (int) $bill->interval_days)),
            default => $anchor->addMonthNoOverflow(),
        };
    }
}
