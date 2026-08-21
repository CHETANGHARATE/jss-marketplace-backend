<?php

namespace App\Listeners;

use App\Events\InventoryLow;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyLowStockListener implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(InventoryLow $event): void
    {
        $product = $event->product;
        $stock = $event->currentStock;
        $reorder = $event->reorderLevel;

        $payload = [
            'product_name' => $product->name,
            'product_id' => $product->id,
            'current_stock' => $stock,
            'reorder_level' => $reorder,
        ];

        // 1. Notify Seller if product belongs to a seller
        if ($product->vendor_id) {
            $this->notificationService->sendToSeller(
                $product->vendor_id,
                'low_stock',
                "Low Stock Alert: {$product->name} ⚠️",
                "Your product '{$product->name}' is low on stock ({$stock} units remaining). Please restock soon.",
                $payload
            );
        }

        // 2. Notify Admins
        $this->notificationService->sendToAdmins(
            'low_stock',
            "Low Stock Operational Alert: {$product->name}",
            "Product '{$product->name}' (ID: {$product->id}) has reached {$stock} units (Safety threshold: {$reorder}).",
            $payload
        );
    }
}
