<?php

namespace App\Services;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class InventoryAlertService
{
    /**
     * Check and trigger restock notifications when inventory moves from 0 to positive.
     */
    public function checkRestockSubscriptions(Product $product, int $oldStock, int $newStock): int
    {
        if ($oldStock > 0 || $newStock <= 0) {
            return 0;
        }

        $subscriptions = BackInStockSubscription::where('product_id', $product->id)
            ->where('status', 'active')
            ->get();

        $notifiedCount = 0;

        foreach ($subscriptions as $sub) {
            try {
                UserNotification::create([
                    'user_id' => $sub->user_id,
                    'title' => 'Back in Stock! 📦',
                    'message' => "'{$product->name}' is back in stock with {$newStock} units available. Get yours before it sells out!",
                    'type' => 'back_in_stock',
                    'data' => [
                        'product_id' => $product->id,
                        'product_slug' => $product->slug,
                        'available_stock' => $newStock,
                    ],
                ]);

                $sub->update([
                    'status' => 'notified',
                    'notified_at' => now(),
                ]);

                $notifiedCount++;
            } catch (\Throwable $e) {
                Log::error("Failed to notify user {$sub->user_id} for back in stock alert on product {$product->id}: " . $e->getMessage());
            }
        }

        return $notifiedCount;
    }
}
