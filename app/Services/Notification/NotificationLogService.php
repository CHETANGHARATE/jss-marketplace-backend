<?php

namespace App\Services\Notification;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationLogService
{
    /**
     * Create or check queued log entry with idempotency.
     */
    public function logQueued(
        ?int $userId,
        string $recipientTarget,
        string $channel,
        string $eventKey,
        ?string $templateKey,
        ?string $idempotencyKey,
        ?string $subject,
        string $messageContent,
        array $payloadData = [],
        string $provider = 'system'
    ): ?NotificationLog {
        if ($idempotencyKey) {
            $existing = NotificationLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existing && in_array($existing->status, ['sent', 'delivered'])) {
                Log::info("IDEMPOTENCY_SKIP: Notification already sent for key [{$idempotencyKey}].");
                return null; // Skip duplicate
            }
            if ($existing) {
                return $existing;
            }
        }

        return NotificationLog::create([
            'user_id' => $userId,
            'recipient_target' => $recipientTarget,
            'channel' => $channel,
            'event_key' => $eventKey,
            'template_key' => $templateKey,
            'idempotency_key' => $idempotencyKey,
            'subject' => $subject,
            'message_content' => $messageContent,
            'payload_data' => $payloadData,
            'provider' => $provider,
            'status' => 'queued',
            'queued_at' => now(),
        ]);
    }

    /**
     * Mark log as sent successfully.
     */
    public function logSent(NotificationLog $log, ?string $providerMessageId = null, ?array $providerResponse = null): void
    {
        $log->update([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'provider_response' => $providerResponse,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark log as failed.
     */
    public function logFailed(NotificationLog $log, string $errorMessage, ?array $providerResponse = null): void
    {
        $log->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'provider_response' => $providerResponse,
            'failed_at' => now(),
            'retry_count' => $log->retry_count + 1,
        ]);
    }
}
