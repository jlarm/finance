<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('target_amount', 14, 2)->unsigned();
            $table->decimal('current_amount', 14, 2)->unsigned()->default(0)->comment('Mutated via BalanceService only');
            $table->date('target_date')->nullable();
            $table->boolean('is_achieved')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_achieved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
