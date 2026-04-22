<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Database\Factories\BudgetTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category', 'period_month', 'amount'])]
class BudgetTarget extends Model
{
    /** @use HasFactory<BudgetTargetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'period_month' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('period_month', $year)->whereMonth('period_month', $month);
    }
}
