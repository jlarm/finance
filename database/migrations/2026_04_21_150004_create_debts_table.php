<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('type', ['credit_card', 'student', 'auto', 'mortgage', 'personal', 'medical', 'other']);
            $table->decimal('balance', 14, 2)->unsigned()->comment('Mutated via BalanceService only');
            $table->decimal('original_balance', 14, 2)->unsigned()->nullable()->comment('For progress display');
            $table->decimal('apr', 5, 2)->unsigned()->nullable()->comment('Annual percentage rate, e.g. 24.99');
            $table->decimal('minimum_payment', 12, 2)->unsigned()->nullable();
            $table->unsignedTinyInteger('due_day')->nullable()->comment('Day of month, 1–31');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
