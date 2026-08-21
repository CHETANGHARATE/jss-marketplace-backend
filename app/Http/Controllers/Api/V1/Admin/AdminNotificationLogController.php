<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationLogController extends Controller
{
    /**
     * List notification delivery logs with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::with('user:id,name,email,mobile')->latest();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('event_key')) {
            $query->where('event_key', $request->event_key);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_target', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message_content', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }

    /**
     * Summary statistics for notification channels and deliveries.
     */
    public function stats(): JsonResponse
    {
        $total = NotificationLog::count();
        $sent = NotificationLog::whereIn('status', ['sent', 'delivered'])->count();
        $failed = NotificationLog::where('status', 'failed')->count();
        $queued = NotificationLog::where('status', 'queued')->count();

        $byChannel = NotificationLog::selectRaw('channel, count(*) as total, sum(case when status in ("sent", "delivered") then 1 else 0 end) as success_count, sum(case when status = "failed" then 1 else 0 end) as fail_count')
            ->groupBy('channel')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'queued' => $queued,
                'success_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 100,
                'by_channel' => $byChannel,
            ],
        ], 200);
    }

    /**
     * Manually retry a failed notification.
     */
    public function retry(int $id): JsonResponse
    {
        $log = NotificationLog::findOrFail($id);

        if (!$log->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot retry notification without an associated user ID.',
            ], 422);
        }

        $log->update([
            'status' => 'queued',
            'error_message' => null,
            'queued_at' => now(),
        ]);

        SendNotificationJob::dispatch(
            $log->user_id,
            $log->channel,
            $log->event_key,
            $log->subject,
            $log->message_content,
            $log->payload_data ?? [],
            $log->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification re-queued for delivery.',
            'data' => $log,
        ], 200);
    }
}
