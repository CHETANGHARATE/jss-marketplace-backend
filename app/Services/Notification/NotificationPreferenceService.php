<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\UserNotificationPreference;

class NotificationPreferenceService
{
    /**
     * Check if notification should be dispatched to a channel for a user based on preferences.
     */
    public function isChannelAllowed(User $user, string $channel, string $eventKey): bool
    {
        // Transactional and security notifications are mandatory and cannot be suppressed
        if ($this->isMandatoryEvent($eventKey)) {
            return true;
        }

        $pref = $user->notificationPreferences;
        if (!$pref) {
            // Default: All channels enabled
            return true;
        }

        // 1. Channel-level check
        $channelEnabled = match ($channel) {
            'email' => $pref->email_enabled,
            'sms' => $pref->sms_enabled,
            'whatsapp' => $pref->whatsapp_enabled,
            'in_app' => $pref->in_app_enabled,
            default => true,
        };

        if (!$channelEnabled) {
            return false;
        }

        // 2. Topic-level check
        $topic = $this->resolveTopicFromEvent($eventKey);
        if ($topic && isset($pref->$topic)) {
            return (bool) $pref->$topic;
        }

        return true;
    }

    /**
     * Determine user's preferred language.
     */
    public function getPreferredLanguage(User $user): string
    {
        $pref = $user->notificationPreferences;
        return $pref?->preferred_language ?: 'en';
    }

    /**
     * Mandatory events (cannot be opted out).
     */
    public function isMandatoryEvent(string $eventKey): bool
    {
        $mandatoryEvents = [
            'order_placed',
            'order_confirmed',
            'order_shipped',
            'order_delivered',
            'order_cancelled',
            'item_cancelled',
            'refund_initiated',
            'refund_completed',
            'return_status_updated',
            'otp_verification',
            'password_reset',
            'business_verification',
            'credit_approved',
            'po_issued',
        ];

        return in_array($eventKey, $mandatoryEvents, true);
    }

    /**
     * Map event key to preference column.
     */
    protected function resolveTopicFromEvent(string $eventKey): ?string
    {
        if (str_starts_with($eventKey, 'order_') || str_starts_with($eventKey, 'return_') || str_starts_with($eventKey, 'refund_')) {
            return 'order_updates';
        }
        if (str_starts_with($eventKey, 'price_drop')) {
            return 'price_alerts';
        }
        if (str_starts_with($eventKey, 'back_in_stock') || str_starts_with($eventKey, 'product_launch')) {
            return 'stock_alerts';
        }
        if (str_starts_with($eventKey, 'store_')) {
            return 'store_updates';
        }
        if (str_starts_with($eventKey, 'promo_') || str_starts_with($eventKey, 'marketing_')) {
            return 'promotions';
        }
        if (str_starts_with($eventKey, 'abandoned_cart')) {
            return 'abandoned_cart';
        }

        return null;
    }
}
