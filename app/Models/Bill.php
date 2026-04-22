<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id',
    'category',
    'debt_id',
    'name',
    'amount',
    'frequency',
    'interval_days',
    'next_due_on',
    'last_paid_on',
    'autopay_reminder',
    'split_across_paychecks',
    'is_active',
    'notes',
])]
class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'interval_days' => 'integer',
            'next_due_on' => 'date:Y-m-d',
            'last_paid_on' => 'date:Y-m-d',
            'autopay_reminder' => 'boolean',
            'split_across_paychecks' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDueWithin(Builder $query, int $days): Builder
    {
        return $query->whereBetween('next_due_on', [today(), today()->addDays($days)]);
    }

    protected function status(): Attribute
    {
        return Attribute::get(function (): string {
            $due = Carbon::parse($this->next_due_on)->startOfDay();
            $today = today();

            return match (true) {
                $due->lt($today) => 'overdue',
                $due->equalTo($today) => 'due_today',
                $due->diffInDays($today) <= 3 => 'due_soon',
                default => 'upcoming',
            };
        });
    }
}
