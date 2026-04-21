<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetTargetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('bills', BillController::class)->except('show');
    Route::resource('debts', DebtController::class);
    Route::resource('income-sources', IncomeSourceController::class)->except('show');
    Route::resource('savings-goals', SavingsGoalController::class);

    Route::resource('budget-targets', BudgetTargetController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('planner', PlannerController::class)->name('planner');

    Route::resource('insights', InsightController::class)
        ->only(['index', 'update', 'destroy']);

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('spending', [ReportController::class, 'spending'])->name('spending');
        Route::get('cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
    });

    Route::get('settings/finance', [SettingsController::class, 'edit'])->name('finance-settings.edit');
    Route::patch('settings/finance', [SettingsController::class, 'update'])->name('finance-settings.update');

    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant.index');
    Route::post('assistant/chat', [AssistantController::class, 'store'])->name('assistant.chat');
});

require __DIR__.'/settings.php';
