<?php

namespace App\Models;

use Database\Factories\SavingsGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'target_amount',
    'current_amount',
    'target_date',
    'is_achieved',
    'notes',
])]
class SavingsGoal extends Model
{
    /** @use HasFactory<SavingsGoalFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['progress_percentage'];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date:Y-m-d',
            'is_achieved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_achieved', false);
    }

    protected function progressPercentage(): Attribute
    {
        return Attribute::get(function (): float {
            if ((float) $this->target_amount <= 0) {
                return 0.0;
            }

            return round(min(100, ((float) $this->current_amount / (float) $this->target_amount) * 100), 2);
        });
    }
}
