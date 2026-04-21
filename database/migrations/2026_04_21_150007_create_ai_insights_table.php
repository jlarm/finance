<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', [
                'spending_spike',
                'bill_reminder',
                'debt_progress',
                'goal_progress',
                'cashflow_warning',
                'budget_overrun',
                'tip',
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title', 160);
            $table->text('body');
            $table->json('data')->nullable()->comment('Structured payload backing the insight');
            $table->enum('status', ['new', 'dismissed', 'acted_on'])->default('new');
            $table->date('generated_for_period')->nullable()->comment('First of month this insight covers');
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['user_id', 'kind', 'generated_for_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
