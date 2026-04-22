<?php

namespace App\Models;

use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'type',
    'balance',
    'original_balance',
    'apr',
    'minimum_payment',
    'due_day',
    'is_active',
    'notes',
])]
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['progress_percentage'];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'original_balance' => 'decimal:2',
            'apr' => 'decimal:2',
            'minimum_payment' => 'decimal:2',
            'due_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function progressPercentage(): Attribute
    {
        return Attribute::get(function (): ?float {
            if (! $this->original_balance || (float) $this->original_balance <= 0) {
                return null;
            }

            $paidDown = (float) $this->original_balance - (float) $this->balance;

            return round(max(0, min(100, ($paidDown / (float) $this->original_balance) * 100)), 2);
        });
    }
}
