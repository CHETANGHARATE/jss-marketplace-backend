<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    protected Msg91Service $msg91Service;

    public function __construct(Msg91Service $msg91Service)
    {
        $this->msg91Service = $msg91Service;
    }

    /**
     * Notify customer on order placed.
     */
    public function notifyOrderPlaced(Order $order): void
    {
        $customer = $order->user;
        $orderNum = $order->order_number;
        $total = number_format((float) $order->total_amount, 2);

        $title = "Order Placed Successfully! 🎉";
        $message = "Your order #{$orderNum} for ₹{$total} has been confirmed on JSS Marketplace and is being prepared.";

        $this->dispatchMultiChannelNotification(
            $customer,
            'order_placed',
            $title,
            $message,
            [
                'order_number' => $orderNum,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method,
            ],
            $order->shipping_address_snapshot['phone'] ?? $customer->mobile ?? null
        );
    }

    /**
     * Notify customer on order status update (Confirmed, Shipped, Delivered, etc.).
     */
    public function notifyOrderStatusUpdated(Order $order, string $status): void
    {
        $customer = $order->user;
        $orderNum = $order->order_number;

        $statusTitles = [
            'confirmed' => "Order #{$orderNum} Confirmed ✅",
            'processing' => "Order #{$orderNum} is Being Packed 📦",
            'shipped' => "Order #{$orderNum} Shipped & On Its Way 🚚",
            'out_for_delivery' => "Order #{$orderNum} is Out for Delivery 🛵",
            'delivered' => "Order #{$orderNum} Delivered Successfully! 🎁",
            'cancelled' => "Order #{$orderNum} Cancelled",
        ];

        $statusMessages = [
            'confirmed' => "Great news! Your order #{$orderNum} has been accepted by the seller.",
            'processing' => "Your items for order #{$orderNum} have been packed and are ready for dispatch.",
            'shipped' => "Your order #{$orderNum} has been handed over to our courier partner. You can track real-time status in My Orders.",
            'out_for_delivery' => "Our delivery executive is out for delivery with your package #{$orderNum}.",
            'delivered' => "Your order #{$orderNum} was delivered! Thank you for shopping on JSS Marketplace.",
            'cancelled' => "Your order #{$orderNum} has been cancelled. If any payment was deducted, a refund will be processed.",
        ];

        $title = $statusTitles[$status] ?? "Order #{$orderNum} Update";
        $message = $statusMessages[$status] ?? "Your order status is now: " . ucfirst(str_replace('_', ' ', $status));

        $this->dispatchMultiChannelNotification(
            $customer,
            'order_status_' . $status,
            $title,
            $message,
            [
                'order_number' => $orderNum,
                'status' => $status,
            ],
            $order->shipping_address_snapshot['phone'] ?? $customer->mobile ?? null
        );
    }

    /**
     * Notify customer on single line-item cancellation.
     */
    public function notifyItemCancelled(Order $order, OrderItem $item, string $reason): void
    {
        $customer = $order->user;
        $orderNum = $order->order_number;
        $itemName = $item->product_name;

        $title = "Item Cancelled from Order #{$orderNum}";
        $message = "Item '{$itemName}' was cancelled from your order #{$orderNum}. Reason: {$reason}. Remaining items in your order remain active.";

        $this->dispatchMultiChannelNotification(
            $customer,
            'item_cancelled',
            $title,
            $message,
            [
                'order_number' => $orderNum,
                'item_id' => $item->id,
                'item_name' => $itemName,
                'reason' => $reason,
            ],
            $order->shipping_address_snapshot['phone'] ?? $customer->mobile ?? null
        );
    }

    /**
     * Core multi-channel notification dispatcher (In-app, Email, SMS, WhatsApp).
     */
    protected function dispatchMultiChannelNotification(
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $phone = null
    ): void {
        if (!$user) {
            return;
        }

        // 1. In-App User Notification (Always Recorded)
        try {
            UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error("IN_APP_NOTIFICATION_FAILED for user [{$user->id}]: " . $e->getMessage());
        }

        // 2. Transactional Email
        if (!empty($user->email)) {
            try {
                Mail::raw("{$title}\n\n{$message}\n\nThank you,\nJSS Solutions Marketplace Team\nhttps://jsssolutions.in", function ($mail) use ($user, $title) {
                    $mail->to($user->email)
                        ->from('no-reply@jsssolutions.in', 'JSS Marketplace')
                        ->subject($title);
                });
                Log::info("ORDER_EMAIL_NOTIF_SENT: User [{$user->id}], Email [{$user->email}]");
            } catch (\Throwable $e) {
                Log::warning("ORDER_EMAIL_NOTIF_SKIPPED to [{$user->email}]: " . $e->getMessage());
            }
        }

        // 3. Transactional SMS via MSG91
        $targetMobile = $phone ?: $user->mobile;
        if (!empty($targetMobile)) {
            try {
                $smsResult = $this->msg91Service->sendTransactionalMessage($targetMobile, "{$title}\n{$message}");
                Log::info("ORDER_SMS_NOTIF_DISPATCHED to [{$targetMobile}]: " . json_encode($smsResult));
            } catch (\Throwable $e) {
                Log::warning("ORDER_SMS_NOTIF_FAILED to [{$targetMobile}]: " . $e->getMessage());
            }
        }
    }
}
