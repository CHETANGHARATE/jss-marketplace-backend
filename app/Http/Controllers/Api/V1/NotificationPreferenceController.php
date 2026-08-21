<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Get authenticated user's notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $pref = UserNotificationPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_enabled' => true,
                'sms_enabled' => true,
                'whatsapp_enabled' => true,
                'in_app_enabled' => true,
                'order_updates' => true,
                'price_alerts' => true,
                'stock_alerts' => true,
                'store_updates' => true,
                'promotions' => true,
                'abandoned_cart' => true,
                'preferred_language' => 'en',
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $pref,
        ], 200);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'whatsapp_enabled' => 'sometimes|boolean',
            'in_app_enabled' => 'sometimes|boolean',
            'order_updates' => 'sometimes|boolean',
            'price_alerts' => 'sometimes|boolean',
            'stock_alerts' => 'sometimes|boolean',
            'store_updates' => 'sometimes|boolean',
            'promotions' => 'sometimes|boolean',
            'abandoned_cart' => 'sometimes|boolean',
            'preferred_language' => 'sometimes|string|in:en,hi,mr',
        ]);

        $pref = UserNotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
            'data' => $pref,
        ], 200);
    }
}
