<?php

namespace App\Services\Notification;

use App\Jobs\SendNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected NotificationTemplateService $templateService;
    protected NotificationPreferenceService $preferenceService;
    protected NotificationLogService $logService;

    public function __construct(
        NotificationTemplateService $templateService,
        NotificationPreferenceService $preferenceService,
        NotificationLogService $logService
    ) {
        $this->templateService = $templateService;
        $this->preferenceService = $preferenceService;
        $this->logService = $logService;
    }

    /**
     * Dispatch multi-channel notification to a recipient user.
     *
     * @param string $eventKey E.g. 'order_placed', 'price_drop', 'back_in_stock', 'product_launch', 'low_stock'
     * @param User $recipient The recipient user model
     * @param array $data Dynamic variable data array
     * @param array|null $forcedChannels Specific channels to dispatch e.g. ['in_app', 'email']
     * @param string|null $idempotencyKey Optional unique key to avoid duplicate notifications
     */
    public function send(
        string $eventKey,
        User $recipient,
        array $data = [],
        ?array $forcedChannels = null,
        ?string $idempotencyKey = null,
        bool $sync = false
    ): array {
        $availableChannels = ['in_app', 'email', 'sms', 'whatsapp'];
        $targetChannels = $forcedChannels ?? $availableChannels;
        $language = $this->preferenceService->getPreferredLanguage($recipient);

        // Inject standard recipient data if not provided
        $data['name'] = $data['name'] ?? $recipient->name;
        $data['email'] = $data['email'] ?? $recipient->email;
        $data['mobile'] = $data['mobile'] ?? $recipient->mobile;

        $results = [];

        foreach ($targetChannels as $channel) {
            // Check preference compliance (exempts transactional/security)
            if (!$this->preferenceService->isChannelAllowed($recipient, $channel, $eventKey)) {
                Log::info("NOTIF_PREF_SUPPRESSED: User [{$recipient->id}], Channel [{$channel}], Event [{$eventKey}]");
                continue;
            }

            // Render template
            $rendered = $this->templateService->render($eventKey, $channel, $language, $data);
            $subject = $rendered['subject'] ?? null;
            $body = $rendered['body'] ?? '';

            // Unique channel-level idempotency key
            $channelIdempotencyKey = $idempotencyKey ? "{$idempotencyKey}_{$channel}" : null;

            // Target identifier
            $target = match ($channel) {
                'email' => $recipient->email ?? (string) $recipient->id,
                'sms', 'whatsapp' => $recipient->mobile ?? (string) $recipient->id,
                default => (string) $recipient->id,
            };

            // Provider mapping
            $provider = match ($channel) {
                'sms' => 'msg91',
                'whatsapp' => 'whatsapp_cloud',
                'email' => 'smtp',
                default => 'in_app',
            };

            // Create initial queued log entry
            $log = $this->logService->logQueued(
                $recipient->id,
                $target,
                $channel,
                $eventKey,
                $rendered['template_id'] ? (string)$rendered['template_id'] : $eventKey,
                $channelIdempotencyKey,
                $subject,
                $body,
                $data,
                $provider
            );

            // If log returned null, duplicate suppressed by idempotency
            if ($channelIdempotencyKey && !$log) {
                $results[$channel] = 'skipped_idempotent';
                continue;
            }

            // Dispatch job
            if ($sync) {
                SendNotificationJob::dispatchSync(
                    $recipient->id,
                    $channel,
                    $eventKey,
                    $subject,
                    $body,
                    $data,
                    $log?->id
                );
            } else {
                SendNotificationJob::dispatch(
                    $recipient->id,
                    $channel,
                    $eventKey,
                    $subject,
                    $body,
                    $data,
                    $log?->id
                );
            }

            $results[$channel] = 'queued';
        }

        return $results;
    }

    /**
     * Dispatch operational notification to all active Admin / Staff members.
     */
    public function sendToAdmins(string $eventKey, string $title, string $message, array $data = []): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();
            foreach ($admins as $admin) {
                $this->send(
                    $eventKey,
                    $admin,
                    array_merge($data, ['subject' => $title, 'message' => $message]),
                    ['in_app', 'email']
                );
            }
        } catch (\Throwable $e) {
            Log::error("ADMIN_NOTIF_DISPATCH_FAILED: " . $e->getMessage());
        }
    }

    /**
     * Dispatch operational notification to a Vendor / Seller.
     */
    public function sendToSeller(int $sellerId, string $eventKey, string $title, string $message, array $data = []): void
    {
        try {
            $seller = User::find($sellerId);
            if ($seller) {
                $this->send(
                    $eventKey,
                    $seller,
                    array_merge($data, ['subject' => $title, 'message' => $message]),
                    ['in_app', 'email', 'sms', 'whatsapp']
                );
            }
        } catch (\Throwable $e) {
            Log::error("SELLER_NOTIF_DISPATCH_FAILED: " . $e->getMessage());
        }
    }
}
