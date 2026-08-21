<?php

namespace App\Listeners;

use App\Events\ProductRestocked;
use App\Models\BackInStockSubscription;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyBackInStockListener implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(ProductRestocked $event): void
    {
        $product = $event->product;
        $oldStock = $event->oldStock;
        $newStock = $event->newStock;

        if ($oldStock > 0 || $newStock <= 0) {
            return;
        }

        $subscriptions = BackInStockSubscription::where('product_id', $product->id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        foreach ($subscriptions as $sub) {
            if (!$sub->user) continue;

            $idempotencyKey = "back_in_stock_{$sub->id}_{$product->id}_{$newStock}";

            $this->notificationService->send(
                'back_in_stock',
                $sub->user,
                [
                    'product_name' => $product->name,
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'available_stock' => $newStock,
                    'price' => $product->sale_price ?? $product->price,
                ],
                null,
                $idempotencyKey
            );

            $sub->update([
                'status' => 'notified',
                'notified_at' => now(),
            ]);
        }
    }
}
