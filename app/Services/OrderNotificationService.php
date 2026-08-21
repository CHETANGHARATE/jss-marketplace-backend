<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Services\Notification\NotificationService;

class OrderNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Notify customer on order placed (Feature 28).
     */
    public function notifyOrderPlaced(Order $order): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $orderNum = $order->order_number;
        $total = number_format((float) $order->total_amount, 2);

        $this->notificationService->send(
            'order_placed',
            $customer,
            [
                'order_number' => $orderNum,
                'amount' => $total,
                'payment_method' => $order->payment_method,
                'phone' => $order->shipping_address_snapshot['phone'] ?? $customer->mobile,
            ],
            null,
            "order_{$order->id}_placed"
        );
    }

    /**
     * Notify customer on order status update (Confirmed, Shipped, Delivered, etc.).
     */
    public function notifyOrderStatusUpdated(Order $order, string $status): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $orderNum = $order->order_number;

        $eventKey = match ($status) {
            'confirmed' => 'order_confirmed',
            'shipped' => 'order_shipped',
            'delivered' => 'order_delivered',
            'cancelled' => 'order_cancelled',
            default => 'order_status_' . $status,
        };

        $this->notificationService->send(
            $eventKey,
            $customer,
            [
                'order_number' => $orderNum,
                'status' => $status,
                'tracking_number' => $order->tracking_number ?? 'N/A',
                'phone' => $order->shipping_address_snapshot['phone'] ?? $customer->mobile,
            ],
            null,
            "order_{$order->id}_status_{$status}"
        );
    }

    /**
     * Notify customer on single line-item cancellation (Feature 24).
     */
    public function notifyItemCancelled(Order $order, OrderItem $item, string $reason): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $this->notificationService->send(
            'item_cancelled',
            $customer,
            [
                'order_number' => $order->order_number,
                'item_id' => $item->id,
                'product_name' => $item->product_name,
                'reason' => $reason,
                'phone' => $order->shipping_address_snapshot['phone'] ?? $customer->mobile,
            ],
            null,
            "order_{$order->id}_item_{$item->id}_cancelled"
        );
    }

    /**
     * Notify customer on return and refund status updates (Features 36, 37, 39).
     */
    public function sendReturnStatusNotification(OrderReturn $orderReturn): void
    {
        $customer = $orderReturn->user;
        if (!$customer) return;

        $returnNum = $orderReturn->return_number;
        $status = $orderReturn->status;
        $amount = number_format((float) $orderReturn->refund_amount, 2);

        $eventKey = match ($status) {
            'refunded' => 'refund_completed',
            'refund_processing' => 'refund_initiated',
            default => 'return_status_' . $status,
        };

        $this->notificationService->send(
            $eventKey,
            $customer,
            [
                'return_number' => $returnNum,
                'status' => $status,
                'amount' => $amount,
                'phone' => $orderReturn->pickup_address_snapshot['phone'] ?? $customer->mobile,
            ],
            null,
            "return_{$orderReturn->id}_status_{$status}"
        );
    }
}
