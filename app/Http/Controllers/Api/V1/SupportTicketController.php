<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\ReplyTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TicketMessageResource;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    protected SupportTicketService $ticketService;

    public function __construct(SupportTicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * List user's support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => SupportTicketResource::collection($tickets),
        ], 200);
    }

    /**
     * Create a new support ticket.
     */
    public function store(CreateTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ticket = $this->ticketService->createTicket(
            $request->user(),
            $validated['subject'],
            $validated['category'],
            $validated['priority'] ?? 'medium',
            $validated['message'],
            $validated['order_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created.',
            'data' => new SupportTicketResource($ticket),
        ], 201);
    }

    /**
     * View support ticket conversation history.
     */
    public function show(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->where('ticket_number', $ticketNumber)
            ->with(['messages.user', 'order'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new SupportTicketResource($ticket),
        ], 200);
    }

    /**
     * Customer reply to ticket thread.
     */
    public function reply(ReplyTicketRequest $request, string $ticketNumber): JsonResponse
    {
        try {
            $ticket = SupportTicket::where('user_id', $request->user()->id)
                ->where('ticket_number', $ticketNumber)
                ->firstOrFail();

            $validated = $request->validated();
            $message = $this->ticketService->addReply($ticket, $request->user(), $validated['message'], false);

            return response()->json([
                'success' => true,
                'message' => 'Reply added to ticket.',
                'data' => new TicketMessageResource($message),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
