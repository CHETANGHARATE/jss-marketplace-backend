<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Cancel an order and restore stock back to inventory.
     */
    public function cancelOrder(Order $order, string $reason): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new Exception("Order cannot be cancelled at its current status ('{$order->status}').");
        }

        return DB::transaction(function () use ($order, $reason) {
            $primaryWarehouse = Warehouse::where('is_primary', true)->first() 
                ?? Warehouse::where('is_active', true)->first();

            // Restore stock for all items
            foreach ($order->items as $item) {
                if ($item->status === 'cancelled') {
                    continue;
                }

                $whId = $item->warehouse_id ?? $primaryWarehouse?->id;

                if ($whId) {
                    $this->inventoryService->addStock(
                        $whId,
                        $item->product_id,
                        $item->quantity,
                        "ORDER-CANCELLED-{$order->order_number}",
                        "Restored stock from cancelled order {$order->order_number}"
                    );
                } else {
                    $item->product->increment('stock_quantity', $item->quantity);
                    $item->product->update(['stock_status' => 'in_stock']);
                }

                $item->update(['status' => 'cancelled']);
            }

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            return $order->fresh(['items']);
        });
    }

    /**
     * Update order status (Admin / Seller).
     */
    public function updateOrderStatus(Order $order, string $newStatus): Order
    {
        $order->update(['status' => $newStatus]);
        return $order->fresh(['items']);
    }
}
