<?php

namespace App\Services\AI;

use App\AI\Prompts\AssistantAffordabilityPrompt;
use App\AI\Prompts\AssistantGeneralPrompt;
use App\AI\Prompts\InvalidPromptResponseException;
use App\AI\Prompts\Prompt;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Chat assistant pipeline.
 *
 * Each call: validate → intent detect → find/create conversation → build
 * CONTEXT → call AI SDK → validate response → persist user + assistant
 * messages → return a structured payload for the frontend.
 *
 * Grounded to the user's app data. No browsing. No external APIs. Replies
 * only from CONTEXT built by FinancialDataSummaryBuilder.
 */
class ChatFinancialAssistantService
{
    /** Per-user soft cap across a calendar day. */
    private const DAILY_MESSAGE_CAP = 50;

    /** Chat history length included in CONTEXT. */
    private const HISTORY_LIMIT = 12;

    /** Agent identifier persisted on each message. */
    private const AGENT = 'financial-coach';

    public function __construct(
        private FinancialDataSummaryBuilder $summaries,
    ) {}

    /**
     * Handle one chat message.
     *
     * @return array{
     *     conversation_id: ?string,
     *     message: array{
     *         id?: string,
     *         role: string,
     *         answer_type: string,
     *         body: string,
     *         verdict?: ?string,
     *         referenced_values: array<int, array<string, mixed>>,
     *         followup_suggestions?: array<int, string>,
     *         created_at?: string
     *     }
     * }
     */
    public function answer(User $user, string $message, ?string $conversationId = null): array
    {
        if (! ($user->financeSettings?->ai_enabled)) {
            return $this->disabledResponse();
        }

        if (! $this->underDailyCap($user)) {
            return $this->throttledResponse();
        }

        $conversation = $this->findOrCreateConversation($user, $conversationId);
        $history = $this->conversationHistory($conversation);

        $this->persistMessage($conversation, $user, 'user', $message);

        $intent = $this->detectIntent($message);
        $prompt = $this->promptForIntent($intent);
        $context = $intent === 'affordability'
            ? $this->summaries->forAffordability($user, $message)
            : $this->summaries->forChat($user, $message, $history);

        try {
            $raw = $this->callAiSdk($prompt, $context);
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new InvalidPromptResponseException('Model returned non-object JSON.');
            }

            $payload = $prompt->validate($decoded);
        } catch (InvalidPromptResponseException|JsonException $e) {
            $this->logFailure($user, 'invalid_response', $e);

            return $this->errorResponse($conversation);
        } catch (Throwable $e) {
            $this->logFailure($user, 'sdk_error', $e);

            return $this->errorResponse($conversation);
        }

        $assistantMessage = $this->persistMessage(
            conversation: $conversation,
            user: $user,
            role: 'assistant',
            content: (string) ($payload['body'] ?? ''),
            meta: $payload,
        );

        $this->incrementDailyCounter($user);

        return [
            'conversation_id' => $conversation->id,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'answer_type' => (string) ($payload['answer_type'] ?? 'general'),
                'body' => (string) ($payload['body'] ?? ''),
                'verdict' => $payload['verdict'] ?? null,
                'referenced_values' => (array) ($payload['referenced_values'] ?? []),
                'followup_suggestions' => (array) ($payload['followup_suggestions'] ?? []),
                'created_at' => $assistantMessage->created_at?->toIso8601String(),
            ],
        ];
    }

    private function detectIntent(string $message): string
    {
        $lower = strtolower($message);

        $affordabilityCues = ['afford', 'can i buy', 'should i buy', 'fit in my budget', 'fit my budget'];

        foreach ($affordabilityCues as $cue) {
            if (str_contains($lower, $cue)) {
                return 'affordability';
            }
        }

        if (preg_match('/\$?\d+(\.\d+)?/', $message) === 1 && str_contains($lower, 'buy')) {
            return 'affordability';
        }

        return 'general';
    }

    private function promptForIntent(string $intent): Prompt
    {
        return match ($intent) {
            'affordability' => app(AssistantAffordabilityPrompt::class),
            default => app(AssistantGeneralPrompt::class),
        };
    }

    private function findOrCreateConversation(User $user, ?string $conversationId): AgentConversation
    {
        if ($conversationId !== null) {
            $existing = AgentConversation::query()
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return AgentConversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Chat '.now()->toDateString(),
        ]);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function conversationHistory(AgentConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get(['role', 'content'])
            ->map(fn (AgentConversationMessage $m): array => [
                'role' => (string) $m->role,
                'content' => (string) $m->content,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function persistMessage(
        AgentConversation $conversation,
        User $user,
        string $role,
        string $content,
        array $meta = [],
    ): AgentConversationMessage {
        return AgentConversationMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'agent' => self::AGENT,
            'role' => $role,
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function underDailyCap(User $user): bool
    {
        return ((int) Cache::get($this->dailyCounterKey($user), 0)) < self::DAILY_MESSAGE_CAP;
    }

    private function incrementDailyCounter(User $user): void
    {
        $key = $this->dailyCounterKey($user);
        Cache::put($key, ((int) Cache::get($key, 0)) + 1, now()->endOfDay());
    }

    private function dailyCounterKey(User $user): string
    {
        return "ai:chat:{$user->id}:daily:".today()->toDateString();
    }

    /**
     * ADAPTER: only place that calls the Laravel AI SDK from chat. Same
     * shape as FinancialCoachService::callAiSdk(); adapt here if the
     * installed laravel/ai version changes its fluent API.
     *
     * @param  array<string, mixed>  $context
     */
    protected function callAiSdk(Prompt $prompt, array $context): string
    {
        /** @var mixed $ai */
        $ai = app('laravel-ai');

        $response = $ai->text()
            ->using($prompt->model())
            ->withMessages($prompt->buildMessages($context))
            ->withResponseFormat($prompt->schema())
            ->withMaxTokens($prompt->maxTokens())
            ->withTemperature($prompt->temperature())
            ->generate();

        return (string) $response->text();
    }

    /**
     * @return array<string, mixed>
     */
    private function disabledResponse(): array
    {
        return [
            'conversation_id' => null,
            'message' => [
                'role' => 'assistant',
                'answer_type' => 'general',
                'body' => 'AI features are disabled. Turn them on in finance settings to chat.',
                'referenced_values' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function throttledResponse(): array
    {
        return [
            'conversation_id' => null,
            'message' => [
                'role' => 'assistant',
                'answer_type' => 'general',
                'body' => "You've reached the daily chat limit. Try again tomorrow.",
                'referenced_values' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(AgentConversation $conversation): array
    {
        return [
            'conversation_id' => $conversation->id,
            'message' => [
                'role' => 'assistant',
                'answer_type' => 'general',
                'body' => "I couldn't answer that just now. Try rephrasing or ask something else about your data.",
                'referenced_values' => [],
            ],
        ];
    }

    private function logFailure(User $user, string $reason, Throwable $e): void
    {
        Log::warning('Chat assistant reply failed', [
            'user_id' => $user->id,
            'reason' => $reason,
            'message' => $e->getMessage(),
        ]);
    }
}
