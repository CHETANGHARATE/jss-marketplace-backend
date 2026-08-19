<?php

namespace App\Services;

use App\Models\BackInStockSubscription;
use App\Models\PriceDropAlert;
use App\Models\Product;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class PriceAlertService
{
    /**
     * Check and trigger price drop notifications when product price decreases.
     */
    public function checkPriceDropAlerts(Product $product, float $oldPrice, float $newPrice): int
    {
        if ($newPrice >= $oldPrice) {
            return 0;
        }

        $activeAlerts = PriceDropAlert::where('product_id', $product->id)
            ->where('status', 'active')
            ->where(function ($q) use ($newPrice) {
                $q->whereNull('target_price')
                  ->orWhere('target_price', '>=', $newPrice);
            })
            ->get();

        $notifiedCount = 0;

        foreach ($activeAlerts as $alert) {
            try {
                UserNotification::create([
                    'user_id' => $alert->user_id,
                    'title' => 'Price Drop Alert! 🎉',
                    'message' => "Great news! The price of '{$product->name}' dropped from ₹" . number_format($oldPrice, 2) . " to ₹" . number_format($newPrice, 2) . "!",
                    'type' => 'price_drop',
                    'data' => [
                        'product_id' => $product->id,
                        'product_slug' => $product->slug,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                    ],
                ]);

                $alert->update([
                    'status' => 'triggered',
                    'triggered_at' => now(),
                ]);

                $notifiedCount++;
            } catch (\Throwable $e) {
                Log::error("Failed to notify user {$alert->user_id} for price drop on product {$product->id}: " . $e->getMessage());
            }
        }

        return $notifiedCount;
    }
}
