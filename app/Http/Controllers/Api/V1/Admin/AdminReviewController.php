<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\ModerateReviewRequest;
use App\Http\Requests\ReplyTicketRequest;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TicketMessageResource;
use App\Models\ProductQuestion;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Services\ReviewService;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    protected ReviewService $reviewService;
    protected SupportTicketService $ticketService;

    public function __construct(ReviewService $reviewService, SupportTicketService $ticketService)
    {
        $this->reviewService = $reviewService;
        $this->ticketService = $ticketService;
    }

    /**
     * List all reviews for moderation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['product', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $reviews = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
        ], 200);
    }

    /**
     * Moderate a review (approve / reject).
     */
    public function moderate(ModerateReviewRequest $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $validated = $request->validated();

        $updatedReview = $this->reviewService->moderateReview(
            $review,
            $validated['status'],
            $validated['rejection_reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => "Review status updated to '{$validated['status']}'.",
            'data' => new ReviewResource($updatedReview),
        ], 200);
    }

    /**
     * Answer a product question.
     */
    public function answerQuestion(AnswerQuestionRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $question = $this->reviewService->answerQuestion($id, $request->user(), $validated['answer']);

        return response()->json([
            'success' => true,
            'message' => 'Answer saved successfully.',
            'data' => new QuestionResource($question),
        ], 200);
    }

    /**
     * Admin list support tickets.
     */
    public function tickets(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user', 'order']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SupportTicketResource::collection($tickets),
        ], 200);
    }

    /**
     * Admin reply to support ticket.
     */
    public function ticketReply(ReplyTicketRequest $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $validated = $request->validated();

        $message = $this->ticketService->addReply($ticket, $request->user(), $validated['message'], true);

        return response()->json([
            'success' => true,
            'message' => 'Admin reply sent.',
            'data' => new TicketMessageResource($message),
        ], 201);
    }

    /**
     * Update support ticket status.
     */
    public function updateTicketStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $updatedTicket = $this->ticketService->updateTicketStatus($ticket, $request->input('status'));

        return response()->json([
            'success' => true,
            'message' => "Ticket status updated to '{$request->input('status')}'.",
            'data' => new SupportTicketResource($updatedTicket),
        ], 200);
    }
}
