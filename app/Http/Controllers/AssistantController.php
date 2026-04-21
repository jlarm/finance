<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\AgentConversationMessage;
use App\Services\AI\ChatFinancialAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssistantController extends Controller
{
    public function index(Request $request): Response
    {
        $messages = AgentConversationMessage::query()
            ->whereHas('conversation', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->latest('created_at')
            ->limit(20)
            ->get(['id', 'conversation_id', 'role', 'content', 'meta', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn (AgentConversationMessage $message): array => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'role' => $message->role,
                'body' => $message->content,
                'meta' => $this->decodeMeta($message->meta),
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->all();

        $latest = end($messages) ?: null;

        return Inertia::render('assistant/Index', [
            'initialConversationId' => $latest['conversation_id'] ?? null,
            'initialMessages' => $messages,
        ]);
    }

    public function store(ChatMessageRequest $request, ChatFinancialAssistantService $chat): JsonResponse
    {
        $result = $chat->answer(
            user: $request->user(),
            message: trim((string) $request->string('message')),
            conversationId: $request->input('conversation_id'),
        );

        return response()->json($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (! is_string($meta) || $meta === '') {
            return [];
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : [];
    }
}
