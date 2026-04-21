<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Services\AI\ChatFinancialAssistantService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function store(ChatMessageRequest $request, ChatFinancialAssistantService $chat): JsonResponse
    {
        $result = $chat->answer(
            user: $request->user(),
            message: trim((string) $request->string('message')),
            conversationId: $request->input('conversation_id'),
        );

        return response()->json($result);
    }
}
