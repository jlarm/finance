<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160)->comment('Source label, e.g. Employer, Freelance');
            $table->decimal('amount', 12, 2)->unsigned();
            $table->date('received_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'received_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_sources');
    }
};
