<?php

namespace App\Listeners;

use App\Events\ProductLaunched;
use App\Models\ProductLaunchSubscription;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyProductLaunchListener implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(ProductLaunched $event): void
    {
        $product = $event->product;

        $subscriptions = ProductLaunchSubscription::where('product_id', $product->id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        foreach ($subscriptions as $sub) {
            if (!$sub->user) continue;

            $idempotencyKey = "launch_{$sub->id}_{$product->id}";

            $this->notificationService->send(
                'product_launch',
                $sub->user,
                [
                    'product_name' => $product->name,
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
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
