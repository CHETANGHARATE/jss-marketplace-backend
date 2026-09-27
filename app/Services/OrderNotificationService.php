<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Services\Notification\NotificationService;

class OrderNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Build rich, structured order data array for professional email templates.
     */
    protected function buildOrderPayload(Order $order, array $extra = []): array
    {
        $order->loadMissing(['items.product', 'user']);

        // Format items array
        $itemsList = [];
        foreach ($order->items as $item) {
            $itemsList[] = [
                'name' => $item->product_name,
                'product_name' => $item->product_name,
                'sku' => $item->product_sku,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->subtotal,
                'subtotal' => (float) $item->subtotal,
            ];
        }

        // Format shipping address string
        $addr = $order->shipping_address_snapshot ?? [];
        $addressLines = [];
        if (!empty($addr['full_name'])) $addressLines[] = $addr['full_name'];
        if (!empty($addr['address_line_1'])) $addressLines[] = $addr['address_line_1'];
        if (!empty($addr['address_line_2'])) $addressLines[] = $addr['address_line_2'];
        $cityStateZip = array_filter([$addr['city'] ?? null, $addr['state'] ?? null, $addr['pincode'] ?? null]);
        if (!empty($cityStateZip)) $addressLines[] = implode(', ', $cityStateZip);
        if (!empty($addr['country'])) $addressLines[] = $addr['country'];
        if (!empty($addr['phone'])) $addressLines[] = 'Phone: ' . $addr['phone'];
        $shippingAddressText = implode("\n", $addressLines);

        $dateFormatted = $order->created_at ? $order->created_at->format('d M Y, h:i A') : date('d M Y');

        $base = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => $dateFormatted,
            'amount' => (float) $order->total_amount,
            'total' => (float) $order->total_amount,
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->tax_amount,
            'shipping' => (float) $order->shipping_amount,
            'discount' => (float) ($order->discount_amount + ($order->loyalty_discount_amount ?? 0)),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'shipping_address_text' => $shippingAddressText,
            'items' => $itemsList,
            'item_count' => count($itemsList),
            'phone' => $addr['phone'] ?? $order->user?->phone,
            'cta_url' => "https://jsssolutions.in/orders/{$order->order_number}",
            'cta_text' => 'View Order Details',
        ];

        return array_merge($base, $extra);
    }

    /**
     * Notify customer on initial order placement.
     * Note: For online/prepaid orders, confirmation is deferred until payment is captured.
     * For Cash on Delivery (COD), order placement acts as COD confirmation.
     */
    public function notifyOrderPlaced(Order $order): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $isCod = strtolower($order->payment_method) === 'cod';

        $payload = $this->buildOrderPayload($order, [
            'status_badge' => $isCod ? 'Order Placed (COD)' : 'Payment Pending',
            'status_badge_type' => $isCod ? 'info' : 'warning',
            'payment_status' => $order->payment_status,
        ]);

        $this->notificationService->send(
            'order_placed',
            $customer,
            $payload,
            null,
            "order_{$order->id}_placed"
        );
    }

    /**
     * Notify customer when online payment is verified and captured (Server-Authoritative & Idempotent).
     */
    public function notifyPaymentConfirmed(Order $order, ?Payment $payment = null): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $payload = $this->buildOrderPayload($order, [
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'transaction_id' => $payment?->transaction_id ?? 'N/A',
            'status_badge' => 'Order Confirmed & Paid',
            'status_badge_type' => 'success',
            'cta_text' => 'View Confirmed Order',
        ]);

        $this->notificationService->send(
            'order_confirmed',
            $customer,
            $payload,
            null,
            "order_{$order->id}_payment_confirmed"
        );
    }

    /**
     * Notify customer if online payment fails.
     */
    public function notifyPaymentFailed(Order $order, ?string $reason = null): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $payload = $this->buildOrderPayload($order, [
            'status' => 'payment_failed',
            'payment_status' => 'failed',
            'failure_reason' => $reason ?: 'Payment authorization could not be completed.',
            'status_badge' => 'Payment Failed',
            'status_badge_type' => 'danger',
            'cta_text' => 'Retry Payment',
        ]);

        $this->notificationService->send(
            'order_payment_failed',
            $customer,
            $payload,
            null,
            "order_{$order->id}_payment_failed_" . time()
        );
    }

    /**
     * Notify customer on order status update (Confirmed, Shipped, Delivered, Cancelled).
     */
    public function notifyOrderStatusUpdated(Order $order, string $status): void
    {
        $customer = $order->user;
        if (!$customer) return;

        $eventKey = match ($status) {
            'confirmed' => 'order_confirmed',
            'shipped' => 'order_shipped',
            'delivered' => 'order_delivered',
            'cancelled' => 'order_cancelled',
            default => 'order_status_' . $status,
        };

        $badgeType = match ($status) {
            'delivered', 'confirmed' => 'success',
            'shipped' => 'info',
            'cancelled' => 'danger',
            default => 'info',
        };

        $payload = $this->buildOrderPayload($order, [
            'status' => $status,
            'status_badge' => ucfirst($status),
            'status_badge_type' => $badgeType,
            'tracking_number' => $order->tracking_number ?? 'N/A',
            'tracking_url' => $order->tracking_number ? "https://jsssolutions.in/track/{$order->tracking_number}" : null,
            'cta_text' => $status === 'shipped' ? 'Track Shipment' : 'View Order Details',
        ]);

        $this->notificationService->send(
            $eventKey,
            $customer,
            $payload,
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

        $payload = $this->buildOrderPayload($order, [
            'item_id' => $item->id,
            'product_name' => $item->product_name,
            'reason' => $reason,
            'status_badge' => 'Item Cancelled',
            'status_badge_type' => 'danger',
        ]);

        $this->notificationService->send(
            'item_cancelled',
            $customer,
            $payload,
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

        $badgeType = match ($status) {
            'refunded' => 'success',
            'refund_processing' => 'info',
            default => 'warning',
        };

        $this->notificationService->send(
            $eventKey,
            $customer,
            [
                'return_number' => $returnNum,
                'status' => $status,
                'status_badge' => ucfirst(str_replace('_', ' ', $status)),
                'status_badge_type' => $badgeType,
                'amount' => (float) $orderReturn->refund_amount,
                'refund_amount' => $amount,
                'phone' => $orderReturn->pickup_address_snapshot['phone'] ?? $customer->phone,
                'cta_url' => 'https://jsssolutions.in/orders',
                'cta_text' => 'View Return Details',
            ],
            null,
            "return_{$orderReturn->id}_status_{$status}"
        );
    }
}
