<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationTemplateController extends Controller
{
    /**
     * List all notification templates with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }
        if ($request->filled('template_key')) {
            $query->where('template_key', 'like', '%' . $request->template_key . '%');
        }

        $templates = $query->orderBy('template_key')->orderBy('channel')->get();

        // If empty, auto-seed defaults
        if ($templates->isEmpty()) {
            $this->seedInitialTemplates();
            $templates = NotificationTemplate::orderBy('template_key')->orderBy('channel')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $templates,
        ], 200);
    }

    /**
     * Show single notification template.
     */
    public function show(int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ], 200);
    }

    /**
     * Update notification template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'dlt_template_id' => 'nullable|string|max:100',
            'whatsapp_template_name' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification template updated successfully.',
            'data' => $template,
        ], 200);
    }

    /**
     * Preview notification template with sample dynamic data without dispatching real communications.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $sampleData = [
            'name' => 'Rahul Sharma',
            'order_number' => 'ORD-20260927-SAMPLE',
            'order_date' => date('d M Y, h:i A'),
            'amount' => 1499.00,
            'total' => 1499.00,
            'subtotal' => 1200.00,
            'tax' => 216.00,
            'shipping' => 83.00,
            'discount' => 0.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'shipping_address_text' => "Rahul Sharma\nFlat 402, Lotus Residency\nAndheri West, Mumbai, Maharashtra 400053\nPhone: +919876543210",
            'items' => [
                [
                    'name' => 'Premium Organic Honey 500g',
                    'sku' => 'JSS-HON-001',
                    'quantity' => 2,
                    'unit_price' => 600.00,
                    'total_price' => 1200.00,
                ],
            ],
            'tracking_number' => 'AWB987654321',
            'tracking_url' => 'https://jsssolutions.in/track/AWB987654321',
            'status_badge' => 'Order Confirmed & Paid',
            'status_badge_type' => 'success',
            'cta_text' => 'View Order Details',
            'cta_url' => 'https://jsssolutions.in/orders/ORD-20260927-SAMPLE',
        ];

        // Substitute variables in subject and body
        $subject = $template->subject ?? '';
        $body = $template->body ?? '';
        foreach ($sampleData as $key => $val) {
            if (is_scalar($val)) {
                $subject = str_replace('{' . $key . '}', (string) $val, $subject);
                $body = str_replace('{' . $key . '}', (string) $val, $body);
            }
        }

        $renderedHtml = null;
        if ($template->channel === 'email') {
            $renderedHtml = view('emails.marketplace_layout', [
                'subject' => $subject,
                'body' => $body,
                'data' => $sampleData,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'template_id' => $template->id,
                'template_key' => $template->template_key,
                'channel' => $template->channel,
                'subject' => $subject,
                'body' => $body,
                'rendered_html' => $renderedHtml,
            ],
        ], 200);
    }

    /**
     * Seed initial production notification templates.
     */
    public function seedInitialTemplates(): void
    {
        $templates = [
            // Order Placed (COD Specific)
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed (COD)',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Order #{order_number} Placed! 📦',
                'body' => 'Your order #{order_number} for {amount} has been placed successfully with Cash on Delivery and is being processed.',
                'variables' => ['name', 'order_number', 'amount', 'payment_method'],
                'is_system_locked' => true,
            ],
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed (COD)',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Order Received: #{order_number} — JSS Marketplace',
                'body' => "Hello {name},\n\nThank you for shopping with JSSSolutions Marketplace! Your order #{order_number} totaling {amount} has been received and is being prepared for dispatch.\n\nPayment Method: Cash on Delivery (COD)\n\nYou can track live order status anytime in your My Orders dashboard.",
                'variables' => ['name', 'order_number', 'amount', 'payment_method'],
                'is_system_locked' => true,
            ],

            // Order Confirmed & Paid (Prepaid Online Payments)
            [
                'template_key' => 'order_confirmed',
                'event_name' => 'Order Confirmed & Payment Successful',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Payment Successful & Order #{order_number} Confirmed! 🎉',
                'body' => 'Your payment for order #{order_number} totaling {amount} has been verified and your order is confirmed.',
                'variables' => ['name', 'order_number', 'amount', 'payment_method', 'transaction_id'],
                'is_system_locked' => true,
            ],
            [
                'template_key' => 'order_confirmed',
                'event_name' => 'Order Confirmed & Payment Successful',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Order Confirmed — Payment Successful: #{order_number} 🎉',
                'body' => "Hello {name},\n\nGreat news! Your payment of {amount} has been verified and confirmed. Your order #{order_number} is now officially confirmed and is being prepared for dispatch.\n\nThank you for choosing JSSSolutions Marketplace!",
                'variables' => ['name', 'order_number', 'amount', 'payment_method', 'transaction_id'],
                'is_system_locked' => true,
            ],

            // Order Payment Pending
            [
                'template_key' => 'order_payment_pending',
                'event_name' => 'Order Payment Pending',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Payment Pending — Order #{order_number}',
                'body' => "Hello {name},\n\nYour order #{order_number} for {amount} is awaiting payment completion. Please complete your transaction to confirm your order.",
                'variables' => ['name', 'order_number', 'amount'],
                'is_system_locked' => true,
            ],

            // Order Payment Failed
            [
                'template_key' => 'order_payment_failed',
                'event_name' => 'Order Payment Failed',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Payment Failed — Order #{order_number}',
                'body' => "Hello {name},\n\nWe could not complete your payment for order #{order_number}. You can retry your payment anytime from your orders dashboard without losing your selected items.",
                'variables' => ['name', 'order_number', 'amount', 'failure_reason'],
                'is_system_locked' => true,
            ],

            // Order Shipped
            [
                'template_key' => 'order_shipped',
                'event_name' => 'Order Dispatched / Shipped',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Your Order Has Shipped 🚚: #{order_number}',
                'body' => "Hello {name},\n\nYour order #{order_number} has been dispatched and is on its way to your delivery address.",
                'variables' => ['name', 'order_number', 'tracking_number', 'tracking_url'],
                'is_system_locked' => true,
            ],

            // Order Delivered
            [
                'template_key' => 'order_delivered',
                'event_name' => 'Order Delivered',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Order Delivered! 🎁: #{order_number}',
                'body' => "Hello {name},\n\nYour package for order #{order_number} has been delivered. Thank you for shopping with JSSSolutions Marketplace!",
                'variables' => ['name', 'order_number'],
                'is_system_locked' => true,
            ],

            // Order Cancelled
            [
                'template_key' => 'order_cancelled',
                'event_name' => 'Order Cancelled',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Order Cancelled: #{order_number}',
                'body' => "Hello {name},\n\nYour order #{order_number} has been cancelled. Any deducted amount has been initiated for refund.",
                'variables' => ['name', 'order_number', 'reason'],
                'is_system_locked' => true,
            ],

            // Price Drop Alert
            [
                'template_key' => 'price_drop',
                'event_name' => 'Price Drop Alert',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Price Drop Alert: {product_name}! 🎉',
                'body' => "Price for '{product_name}' dropped from ₹{old_price} to ₹{new_price}! Grab it before stock runs out.",
                'variables' => ['product_name', 'old_price', 'new_price', 'discount_amount'],
                'is_system_locked' => false,
            ],
            [
                'template_key' => 'price_drop',
                'event_name' => 'Price Drop Alert',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Price Drop on {product_name} - JSS Marketplace',
                'body' => "Hello {name},\n\nGreat news! The product '{product_name}' in your watchlist is now on sale for ₹{new_price} (was ₹{old_price}).\n\nOrder now: https://jsssolutions.in/product/{product_slug}",
                'variables' => ['name', 'product_name', 'old_price', 'new_price', 'product_slug'],
                'is_system_locked' => false,
            ],

            // Back in Stock Alert
            [
                'template_key' => 'back_in_stock',
                'event_name' => 'Back in Stock Alert',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Back in Stock: {product_name} 📦',
                'body' => "'{product_name}' is back in stock with {available_stock} units available! Order yours today.",
                'variables' => ['product_name', 'available_stock', 'price', 'product_slug'],
                'is_system_locked' => false,
            ],

            // Product Launch Alert
            [
                'template_key' => 'product_launch',
                'event_name' => 'Product Launch Notification',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Now Available: {product_name}! 🚀',
                'body' => "The product '{product_name}' has officially launched and is now available for purchase.",
                'variables' => ['product_name', 'price', 'product_slug'],
                'is_system_locked' => false,
            ],

            // Store Update
            [
                'template_key' => 'store_update',
                'event_name' => 'Followed Store New Product',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'New from {store_name}! 🏪',
                'body' => "{store_name} just launched a new product: '{product_name}'. Check it out now!",
                'variables' => ['store_name', 'product_name', 'price', 'product_slug'],
                'is_system_locked' => false,
            ],

            // Abandoned Cart Reminder
            [
                'template_key' => 'abandoned_cart',
                'event_name' => 'Abandoned Cart Recovery Reminder',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Your cart is waiting for you! 🛒',
                'body' => "You left items totaling {amount} in your cart. Complete your purchase before items sell out!",
                'variables' => ['name', 'amount', 'item_count'],
                'is_system_locked' => false,
            ],
            [
                'template_key' => 'abandoned_cart',
                'event_name' => 'Abandoned Cart Recovery Reminder',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Did you forget something? Complete your order on JSS Marketplace',
                'body' => "Hello {name},\n\nWe noticed you left items totaling {amount} in your shopping cart. Click below to quickly finish checking out:\n\nhttps://jsssolutions.in/cart\n\nThank you,\nJSS Solutions Marketplace",
                'variables' => ['name', 'amount'],
                'is_system_locked' => false,
            ],

            // Low Stock Operational Alert
            [
                'template_key' => 'low_stock',
                'event_name' => 'Low Stock Operational Alert',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Low Stock Alert: {product_name} ⚠️',
                'body' => "Product '{product_name}' has reached low stock level ({current_stock} remaining). Please replenish inventory.",
                'variables' => ['product_name', 'current_stock', 'reorder_level'],
                'is_system_locked' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            NotificationTemplate::updateOrCreate(
                [
                    'template_key' => $tpl['template_key'],
                    'channel' => $tpl['channel'],
                    'language' => $tpl['language'],
                ],
                $tpl
            );
        }
    }
}
