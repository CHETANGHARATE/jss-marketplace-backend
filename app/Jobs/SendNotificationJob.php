<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\Channels\EmailChannel;
use App\Services\Notification\Channels\InAppChannel;
use App\Services\Notification\Channels\SmsChannel;
use App\Services\Notification\Channels\WhatsAppChannel;
use App\Services\Notification\NotificationLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // 30 seconds backoff between retries

    protected int $userId;
    protected string $channel;
    protected string $eventKey;
    protected ?string $subject;
    protected string $body;
    protected array $data;
    protected ?int $logId;

    public function __construct(
        int $userId,
        string $channel,
        string $eventKey,
        ?string $subject,
        string $body,
        array $data = [],
        ?int $logId = null
    ) {
        $this->userId = $userId;
        $this->channel = $channel;
        $this->eventKey = $eventKey;
        $this->subject = $subject;
        $this->body = $body;
        $this->data = $data;
        $this->logId = $logId;
    }

    public function handle(
        InAppChannel $inApp,
        EmailChannel $email,
        SmsChannel $sms,
        WhatsAppChannel $whatsapp,
        NotificationLogService $logService
    ): void {
        $user = User::find($this->userId);
        if (!$user) {
            Log::warning("NOTIF_JOB_SKIPPED: User ID [{$this->userId}] not found.");
            return;
        }

        $log = $this->logId ? NotificationLog::find($this->logId) : null;

        $result = match ($this->channel) {
            'in_app' => $inApp->send($user, $this->eventKey, $this->subject, $this->body, $this->data),
            'email' => $email->send($user, $this->eventKey, $this->subject, $this->body, $this->data),
            'sms' => $sms->send($user, $this->eventKey, $this->subject, $this->body, $this->data),
            'whatsapp' => $whatsapp->send($user, $this->eventKey, $this->subject, $this->body, $this->data),
            default => ['success' => false, 'error' => "Unsupported channel: {$this->channel}"],
        };

        if ($log) {
            if ($result['success'] ?? false) {
                $logService->logSent($log, $result['provider_message_id'] ?? null, $result['response'] ?? null);
            } else {
                $logService->logFailed($log, $result['error'] ?? 'Delivery failed.', $result['response'] ?? null);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("NOTIF_JOB_FAILED_PERMANENTLY: User [{$this->userId}], Channel [{$this->channel}], Event [{$this->eventKey}]: " . $exception->getMessage());
        if ($this->logId) {
            $log = NotificationLog::find($this->logId);
            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => 'Job failed after all attempts: ' . $exception->getMessage(),
                    'failed_at' => now(),
                ]);
            }
        }
    }
}
