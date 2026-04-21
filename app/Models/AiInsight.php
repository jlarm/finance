<?php

namespace App\Models;

use Database\Factories\AiInsightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'kind',
    'severity',
    'title',
    'body',
    'data',
    'status',
    'generated_for_period',
])]
class AiInsight extends Model
{
    /** @use HasFactory<AiInsightFactory> */
    use HasFactory;

    protected $table = 'ai_insights';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'generated_for_period' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'acted_on']);
    }
}
