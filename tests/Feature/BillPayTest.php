<?php

use App\Models\Bill;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('marking a bill paid advances the due date and sets last_paid_on', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->for($user)->create([
        'frequency' => 'monthly',
        'next_due_on' => '2026-04-22',
        'last_paid_on' => null,
        'amount' => 250.00,
    ]);

    $this->actingAs($user)
        ->post(route('bills.pay', $bill))
        ->assertRedirect(route('bills.index'));

    $bill->refresh();

    expect($bill->last_paid_on->toDateString())->toBe(today()->toDateString())
        ->and($bill->next_due_on->toDateString())->toBe('2026-05-22');
});

test('paying a bill linked to a debt reduces the debt balance by the bill amount', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->for($user)->create([
        'balance' => 20000.00,
        'original_balance' => 25000.00,
    ]);
    $bill = Bill::factory()->for($user)->create([
        'debt_id' => $debt->id,
        'amount' => 450.00,
        'frequency' => 'monthly',
        'next_due_on' => today()->toDateString(),
    ]);

    $this->actingAs($user)->post(route('bills.pay', $bill))->assertRedirect();

    expect($debt->fresh()->balance)->toEqual('19550.00');
});

test('paying a bill without a debt link leaves debts untouched', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->for($user)->create(['balance' => 1000.00]);
    $bill = Bill::factory()->for($user)->create([
        'debt_id' => null,
        'amount' => 100.00,
    ]);

    $this->actingAs($user)->post(route('bills.pay', $bill))->assertRedirect();

    expect($debt->fresh()->balance)->toEqual('1000.00');
});

test('a debt balance floors at zero when a bill payment would overshoot it', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->for($user)->create(['balance' => 100.00]);
    $bill = Bill::factory()->for($user)->create([
        'debt_id' => $debt->id,
        'amount' => 500.00,
    ]);

    $this->actingAs($user)->post(route('bills.pay', $bill))->assertRedirect();

    expect($debt->fresh()->balance)->toEqual('0.00');
});

test('a user cannot mark another user\'s bill as paid', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $bill = Bill::factory()->for($owner)->create();

    $this->actingAs($stranger)
        ->post(route('bills.pay', $bill))
        ->assertForbidden();
});
