<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->decimal('amount', 12, 2)->unsigned()->comment('Expected amount per occurrence');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom']);
            $table->unsignedSmallInteger('interval_days')->nullable()->comment('Only used when frequency = custom');
            $table->date('next_due_on');
            $table->date('last_paid_on')->nullable()->comment('Advanced when user marks bill paid');
            $table->boolean('autopay_reminder')->default(false)->comment('Flag only — no automation');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'next_due_on']);
            $table->index(['user_id', 'expense_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
