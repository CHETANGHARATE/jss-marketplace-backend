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
     * Seed initial production notification templates.
     */
    public function seedInitialTemplates(): void
    {
        $templates = [
            // Order Placed (In-App, Email, SMS, WhatsApp)
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed Confirmation',
                'channel' => 'in_app',
                'language' => 'en',
                'subject' => 'Order #{order_number} Confirmed! 🎉',
                'body' => 'Your order #{order_number} for {amount} is confirmed on JSS Marketplace and is being prepared.',
                'variables' => ['name', 'order_number', 'amount', 'payment_method'],
                'is_system_locked' => true,
            ],
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed Confirmation',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Order Confirmed: #{order_number} - JSS Marketplace',
                'body' => "Hello {name},\n\nThank you for shopping with JSS Solutions Marketplace! Your order #{order_number} totaling {amount} has been successfully placed.\n\nPayment Method: {payment_method}\n\nYou can track live order status anytime in your My Orders dashboard.",
                'variables' => ['name', 'order_number', 'amount', 'payment_method'],
                'is_system_locked' => true,
            ],
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed Confirmation',
                'channel' => 'sms',
                'language' => 'en',
                'subject' => null,
                'body' => 'Your JSS Marketplace order #{order_number} for {amount} is confirmed. Track live: https://jsssolutions.in/orders/{order_number}',
                'variables' => ['order_number', 'amount'],
                'dlt_template_id' => '1007123456789012345',
                'is_system_locked' => true,
            ],
            [
                'template_key' => 'order_placed',
                'event_name' => 'Order Placed Confirmation',
                'channel' => 'whatsapp',
                'language' => 'en',
                'subject' => 'Order #{order_number} Confirmed',
                'body' => "Hello {name}, your order *#{order_number}* for *{amount}* has been confirmed on JSS Marketplace! 🚀\n\nTrack order: https://jsssolutions.in/orders/{order_number}",
                'variables' => ['name', 'order_number', 'amount'],
                'whatsapp_template_name' => 'jss_order_placed_v1',
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
