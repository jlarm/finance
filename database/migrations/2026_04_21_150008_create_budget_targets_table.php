<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->date('period_month')->comment('First day of the month this target applies to');
            $table->decimal('amount', 12, 2)->unsigned();
            $table->timestamps();

            $table->unique(['user_id', 'expense_category_id', 'period_month'], 'budget_targets_user_category_month_unique');
            $table->index(['user_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_targets');
    }
};
