<?php

namespace App\Models;

use Database\Factories\UserFinanceSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'currency',
    'locale',
    'monthly_cycle_start_day',
    'debt_strategy',
    'ai_tone',
    'ai_enabled',
    'timezone',
])]
class UserFinanceSetting extends Model
{
    /** @use HasFactory<UserFinanceSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monthly_cycle_start_day' => 'integer',
            'ai_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
