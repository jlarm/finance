<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('USD')->comment('ISO 4217');
            $table->string('locale', 10)->default('en-US');
            $table->unsignedTinyInteger('monthly_cycle_start_day')->default(1)->comment('Day of month, 1–28');
            $table->enum('debt_strategy', ['snowball', 'avalanche'])->default('avalanche');
            $table->enum('ai_tone', ['supportive', 'direct', 'cheerful', 'neutral'])->default('supportive');
            $table->boolean('ai_enabled')->default(true);
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_finance_settings');
    }
};
