<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'conversation_id',
    'user_id',
    'agent',
    'role',
    'content',
    'attachments',
    'tool_calls',
    'tool_results',
    'usage',
    'meta',
])]
class AgentConversationMessage extends Model
{
    use HasUuids;

    protected $table = 'agent_conversation_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
