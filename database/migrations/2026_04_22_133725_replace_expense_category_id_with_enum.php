<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Destructive: existing rows in these tables rely on the FK we're dropping.
        // Per project direction, data is nuked and the demo seeder repopulates.
        DB::table('bills')->delete();
        DB::table('expenses')->delete();
        DB::table('budget_targets')->delete();

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropIndex(['user_id', 'expense_category_id']);
            $table->dropColumn('expense_category_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('category', 40)->after('user_id');
            $table->index(['user_id', 'category']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropIndex(['user_id', 'expense_category_id', 'occurred_on']);
            $table->dropColumn('expense_category_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category', 40)->after('user_id');
            $table->index(['user_id', 'category', 'occurred_on']);
        });

        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropUnique('budget_targets_user_category_month_unique');
            $table->dropColumn('expense_category_id');
        });

        Schema::table('budget_targets', function (Blueprint $table) {
            $table->string('category', 40)->after('user_id');
            $table->unique(['user_id', 'category', 'period_month'], 'budget_targets_user_category_month_unique');
        });

        Schema::dropIfExists('expense_categories');
    }

    public function down(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'is_archived']);
        });

        Schema::table('budget_targets', function (Blueprint $table) {
            $table->dropUnique('budget_targets_user_category_month_unique');
            $table->dropColumn('category');
        });

        Schema::table('budget_targets', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->after('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'expense_category_id', 'period_month'], 'budget_targets_user_category_month_unique');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'category', 'occurred_on']);
            $table->dropColumn('category');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->after('user_id')->constrained()->restrictOnDelete();
            $table->index(['user_id', 'expense_category_id', 'occurred_on']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'category']);
            $table->dropColumn('category');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->after('user_id')->constrained()->restrictOnDelete();
            $table->index(['user_id', 'expense_category_id']);
        });
    }
};
