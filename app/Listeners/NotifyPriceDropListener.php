<?php

namespace App\Listeners;

use App\Events\PriceChanged;
use App\Models\PriceDropAlert;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyPriceDropListener implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(PriceChanged $event): void
    {
        $product = $event->product;
        $oldPrice = $event->oldPrice;
        $newPrice = $event->newPrice;

        if ($newPrice >= $oldPrice) {
            return;
        }

        $activeAlerts = PriceDropAlert::where('product_id', $product->id)
            ->where('status', 'active')
            ->where(function ($q) use ($newPrice) {
                $q->whereNull('target_price')
                  ->orWhere('target_price', '>=', $newPrice);
            })
            ->with('user')
            ->get();

        foreach ($activeAlerts as $alert) {
            if (!$alert->user) continue;

            $idempotencyKey = "price_drop_{$alert->id}_{$product->id}_" . round($newPrice);

            $this->notificationService->send(
                'price_drop',
                $alert->user,
                [
                    'product_name' => $product->name,
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'discount_amount' => $oldPrice - $newPrice,
                ],
                null,
                $idempotencyKey
            );

            $alert->update([
                'status' => 'triggered',
                'triggered_at' => now(),
            ]);
        }
    }
}
