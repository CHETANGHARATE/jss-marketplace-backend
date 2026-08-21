<?php

namespace App\Listeners;

use App\Events\StoreProductCreated;
use App\Models\StoreFollower;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyStoreFollowersListener implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(StoreProductCreated $event): void
    {
        $store = $event->store;
        $product = $event->product;

        $followers = StoreFollower::where('vendor_store_id', $store->id)
            ->with('user')
            ->get();

        foreach ($followers as $follower) {
            if (!$follower->user) continue;

            $idempotencyKey = "store_prod_{$store->id}_{$product->id}_{$follower->user_id}";

            $this->notificationService->send(
                'store_update',
                $follower->user,
                [
                    'store_name' => $store->store_name,
                    'store_slug' => $store->slug,
                    'product_name' => $product->name,
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'price' => $product->sale_price ?? $product->price,
                ],
                null,
                $idempotencyKey
            );
        }
    }
}
