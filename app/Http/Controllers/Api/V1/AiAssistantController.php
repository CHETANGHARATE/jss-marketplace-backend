<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\AI\AiShoppingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    protected AiShoppingService $aiService;

    public function __construct(AiShoppingService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Send message to AI Shopping Assistant and receive real product recommendations.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:500',
            'session_id' => 'nullable|string|max:100',
        ]);

        $response = $this->aiService->chat(
            $validated['message'],
            $validated['session_id'] ?? null,
            $request->user('sanctum')
        );

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }

    /**
     * Get chat conversation history.
     */
    public function history(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');
        $userId = $request->user('sanctum')?->id;

        $query = AiConversation::latest();

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $history = $query->take(20)->get()->reverse()->values();

        return response()->json([
            'success' => true,
            'data' => $history,
        ], 200);
    }

    /**
     * Clear chat history for the session.
     */
    public function clear(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');
        $userId = $request->user('sanctum')?->id;

        if ($userId) {
            AiConversation::where('user_id', $userId)->delete();
        } elseif ($sessionId) {
            AiConversation::where('session_id', $sessionId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation history cleared.',
        ], 200);
    }
}
