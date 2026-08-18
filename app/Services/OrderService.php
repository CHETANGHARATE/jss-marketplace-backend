<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    protected InventoryService $inventoryService;
    protected OrderNotificationService $notificationService;

    public function __construct(
        InventoryService $inventoryService,
        OrderNotificationService $notificationService
    ) {
        $this->inventoryService = $inventoryService;
        $this->notificationService = $notificationService;
    }

    /**
     * Cancel an entire order and restore stock back to inventory.
     */
    public function cancelOrder(Order $order, string $reason): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new Exception("Order cannot be cancelled at its current status ('{$order->status}').");
        }

        return DB::transaction(function () use ($order, $reason) {
            $primaryWarehouse = Warehouse::where('is_primary', true)->first() 
                ?? Warehouse::where('is_active', true)->first();

            // Restore stock for all non-cancelled items
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

                $item->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                ]);
            }

            // Refund redeemed loyalty points if any (Feature 25)
            if ($order->loyalty_points_redeemed > 0) {
                $loyalty = LoyaltyPoint::firstOrCreate(
                    ['user_id' => $order->user_id],
                    ['points_balance' => 0, 'total_earned' => 0]
                );
                $loyalty->increment('points_balance', $order->loyalty_points_redeemed);

                LoyaltyTransaction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'points' => $order->loyalty_points_redeemed,
                    'type' => 'refunded',
                    'inr_value' => $order->loyalty_discount_amount,
                    'notes' => "Refunded {$order->loyalty_points_redeemed} JSS Coins for cancelled order #{$order->order_number}",
                ]);
            }

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Dispatch cancellation notifications (Feature 39)
            $this->notificationService->notifyOrderStatusUpdated($order, 'cancelled');

            return $order->fresh(['items.product.primaryImage']);
        });
    }

    /**
     * Cancel an individual line item from a multi-item/multi-vendor order (Feature 139).
     */
    public function cancelOrderItem(Order $order, int $orderItemId, string $reason, ?User $user = null): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new Exception("Item cannot be cancelled because the order is already '{$order->status}'.");
        }

        $item = $order->items()->where('id', $orderItemId)->first();
        if (!$item) {
            throw new Exception("Order item not found.");
        }

        if ($item->status === 'cancelled') {
            throw new Exception("This item has already been cancelled.");
        }

        if (!in_array($item->status, ['pending', 'confirmed'])) {
            throw new Exception("Item cannot be cancelled at its current status ('{$item->status}').");
        }

        return DB::transaction(function () use ($order, $item, $reason) {
            $primaryWarehouse = Warehouse::where('is_primary', true)->first() 
                ?? Warehouse::where('is_active', true)->first();

            // 1. Restore stock for this individual item
            $whId = $item->warehouse_id ?? $primaryWarehouse?->id;
            if ($whId) {
                $this->inventoryService->addStock(
                    $whId,
                    $item->product_id,
                    $item->quantity,
                    "ITEM-CANCELLED-{$order->order_number}-{$item->id}",
                    "Restored stock from cancelled line item {$item->product_name}"
                );
            } else {
                $item->product->increment('stock_quantity', $item->quantity);
                $item->product->update(['stock_status' => 'in_stock']);
            }

            // 2. Update item cancellation fields
            $item->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // 3. Check if all items in this order are now cancelled
            $activeItemsCount = $order->items()->where('status', '!=', 'cancelled')->count();

            if ($activeItemsCount === 0) {
                // Refund entire loyalty coins if all items cancelled
                if ($order->loyalty_points_redeemed > 0) {
                    $loyalty = LoyaltyPoint::firstOrCreate(
                        ['user_id' => $order->user_id],
                        ['points_balance' => 0, 'total_earned' => 0]
                    );
                    $loyalty->increment('points_balance', $order->loyalty_points_redeemed);

                    LoyaltyTransaction::create([
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'points' => $order->loyalty_points_redeemed,
                        'type' => 'refunded',
                        'inr_value' => $order->loyalty_discount_amount,
                        'notes' => "Refunded {$order->loyalty_points_redeemed} JSS Coins for cancelled order #{$order->order_number}",
                    ]);
                }

                $order->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => "All items cancelled: " . $reason,
                    'cancelled_at' => now(),
                ]);
            }

            // 4. Dispatch item cancellation notification (Feature 39)
            $this->notificationService->notifyItemCancelled($order, $item, $reason);

            return $order->fresh(['items.product.primaryImage']);
        });
    }

    /**
     * Update order status (Admin / Seller) with automated customer notifications.
     */
    public function updateOrderStatus(Order $order, string $newStatus): Order
    {
        $order->update(['status' => $newStatus]);

        // Trigger real-time multi-channel notification (Feature 39)
        $this->notificationService->notifyOrderStatusUpdated($order, $newStatus);

        return $order->fresh(['items.product.primaryImage']);
    }
}
