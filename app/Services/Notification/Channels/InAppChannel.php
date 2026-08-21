<?php

namespace App\Services\Notification\Channels;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class InAppChannel
{
    /**
     * Dispatch In-App Notification.
     */
    public function send(User $user, string $eventKey, ?string $subject, string $body, array $data = []): array
    {
        try {
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'type' => $eventKey,
                'title' => $subject ?: 'New Notification',
                'message' => $body,
                'data' => $data,
            ]);

            return [
                'success' => true,
                'provider' => 'in_app',
                'provider_message_id' => (string) $notification->id,
                'response' => ['notification_id' => $notification->id],
            ];
        } catch (\Throwable $e) {
            Log::error("IN_APP_DISPATCH_FAILED: " . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'in_app',
                'error' => $e->getMessage(),
            ];
        }
    }
}
