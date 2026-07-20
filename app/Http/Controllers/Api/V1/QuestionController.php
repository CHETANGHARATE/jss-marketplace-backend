<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AskQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\ProductQuestion;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * List public answered questions for a product.
     */
    public function index(int $productId): JsonResponse
    {
        $questions = ProductQuestion::where('product_id', $productId)
            ->where('is_public', true)
            ->whereNotNull('answer')
            ->with(['user', 'answerer'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
        ], 200);
    }

    /**
     * Ask a question on a product.
     */
    public function store(AskQuestionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $question = $this->reviewService->askQuestion(
            $request->user(),
            $validated['product_id'],
            $validated['question']
        );

        return response()->json([
            'success' => true,
            'message' => 'Question submitted successfully.',
            'data' => new QuestionResource($question),
        ], 201);
    }
}
