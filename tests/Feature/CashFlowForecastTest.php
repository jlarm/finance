<?php

use App\Models\Bill;
use App\Models\User;
use App\Services\CashFlowForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a split bill projects as two half-amount occurrences', function () {
    $user = User::factory()->create();
    $due = today()->copy()->addDays(21);

    Bill::factory()->for($user)->create([
        'amount' => 100.00,
        'frequency' => 'monthly',
        'next_due_on' => $due->toDateString(),
        'split_across_paychecks' => true,
        'is_active' => true,
    ]);

    $occurrences = app(CashFlowForecastService::class)->projectBills($user);

    $firstCycle = $occurrences->take(2)->values()->all();

    expect($firstCycle)->toHaveCount(2)
        ->and($firstCycle[0]['due_on'])->toBe($due->copy()->subDays(14)->toDateString())
        ->and((float) $firstCycle[0]['amount'])->toEqual(50.00)
        ->and($firstCycle[1]['due_on'])->toBe($due->toDateString())
        ->and((float) $firstCycle[1]['amount'])->toEqual(50.00);
});

test('an unsplit bill projects as a single full-amount occurrence', function () {
    $user = User::factory()->create();
    $due = today()->copy()->addDays(21);

    Bill::factory()->for($user)->create([
        'amount' => 100.00,
        'frequency' => 'monthly',
        'next_due_on' => $due->toDateString(),
        'split_across_paychecks' => false,
        'is_active' => true,
    ]);

    $occurrences = app(CashFlowForecastService::class)->projectBills($user);

    $first = $occurrences->first();

    expect($first['due_on'])->toBe($due->toDateString())
        ->and((float) $first['amount'])->toEqual(100.00);
});
