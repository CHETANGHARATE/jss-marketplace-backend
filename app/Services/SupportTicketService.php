<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class SupportTicketService
{
    /**
     * Create customer support ticket and initial message thread.
     */
    public function createTicket(
        User $user,
        string $subject,
        string $category,
        string $priority,
        string $initialMessage,
        ?int $orderId = null
    ): SupportTicket {
        return DB::transaction(function () use ($user, $subject, $category, $priority, $initialMessage, $orderId) {
            $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'order_id' => $orderId,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => 'open',
            ]);

            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $initialMessage,
                'is_admin_reply' => false,
            ]);

            return $ticket->fresh(['messages']);
        });
    }

    /**
     * Add reply message to support ticket.
     */
    public function addReply(SupportTicket $ticket, User $user, string $message, bool $isAdmin = false): TicketMessage
    {
        if ($ticket->status === 'closed') {
            throw new Exception("Cannot reply to a closed support ticket.");
        }

        return DB::transaction(function () use ($ticket, $user, $message, $isAdmin) {
            $ticketMessage = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $message,
                'is_admin_reply' => $isAdmin,
            ]);

            $newStatus = $isAdmin ? 'in_progress' : 'open';
            $ticket->update(['status' => $newStatus]);

            return $ticketMessage;
        });
    }

    /**
     * Update ticket status (Admin).
     */
    public function updateTicketStatus(SupportTicket $ticket, string $status): SupportTicket
    {
        $ticket->update(['status' => $status]);
        return $ticket->fresh();
    }
}
