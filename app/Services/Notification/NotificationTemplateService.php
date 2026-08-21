<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class NotificationTemplateService
{
    /**
     * Render template subject and body with variable substitution.
     */
    public function render(string $templateKey, string $channel, string $language = 'en', array $data = []): array
    {
        $template = NotificationTemplate::where('template_key', $templateKey)
            ->where('channel', $channel)
            ->where('language', $language)
            ->where('is_active', true)
            ->first();

        // Fallback to English if specified language not found
        if (!$template && $language !== 'en') {
            $template = NotificationTemplate::where('template_key', $templateKey)
                ->where('channel', $channel)
                ->where('language', 'en')
                ->where('is_active', true)
                ->first();
        }

        if ($template) {
            $subject = $this->substituteVariables($template->subject ?? '', $data);
            $body = $this->substituteVariables($template->body, $data);

            return [
                'subject' => $subject,
                'body' => $body,
                'template_id' => $template->id,
                'dlt_template_id' => $template->dlt_template_id,
                'whatsapp_template_name' => $template->whatsapp_template_name,
            ];
        }

        // Built-in fallback if no database template is defined
        return $this->getBuiltInFallback($templateKey, $channel, $language, $data);
    }

    /**
     * Replace {placeholder} variables with actual values.
     */
    public function substituteVariables(string $content, array $data): string
    {
        if (empty($content)) {
            return '';
        }

        foreach ($data as $key => $val) {
            if (is_scalar($val) || is_null($val)) {
                $content = str_replace('{' . $key . '}', (string) ($val ?? ''), $content);
            }
        }

        return $content;
    }

    /**
     * Built-in fallback templates when DB template record is missing.
     */
    protected function getBuiltInFallback(string $templateKey, string $channel, string $language, array $data): array
    {
        $name = $data['name'] ?? $data['user_name'] ?? 'Valued Customer';
        $orderNum = $data['order_number'] ?? '';
        $amount = isset($data['amount']) ? '₹' . number_format((float)$data['amount'], 2) : '';
        $productName = $data['product_name'] ?? 'Product';
        $oldPrice = isset($data['old_price']) ? '₹' . number_format((float)$data['old_price'], 2) : '';
        $newPrice = isset($data['new_price']) ? '₹' . number_format((float)$data['new_price'], 2) : '';
        $storeName = $data['store_name'] ?? 'Seller';

        $defaults = [
            'order_placed' => [
                'subject' => "Order #{$orderNum} Confirmed! 🎉",
                'body' => "Hello {$name}, your order #{$orderNum} for {$amount} is confirmed on JSS Marketplace and is being prepared.",
            ],
            'order_shipped' => [
                'subject' => "Order #{$orderNum} Shipped 🚚",
                'body' => "Hello {$name}, your order #{$orderNum} has been shipped with tracking number {$data['tracking_number']}.",
            ],
            'order_delivered' => [
                'subject' => "Order #{$orderNum} Delivered! 🎁",
                'body' => "Hello {$name}, your package for order #{$orderNum} was successfully delivered. Thank you for shopping with us!",
            ],
            'price_drop' => [
                'subject' => "Price Drop Alert: {$productName}! 🎉",
                'body' => "Great news {$name}! Price for '{$productName}' dropped from {$oldPrice} to {$newPrice}!",
            ],
            'back_in_stock' => [
                'subject' => "Back in Stock: {$productName} 📦",
                'body' => "Hello {$name}, '{$productName}' is back in stock! Order now before stock runs out.",
            ],
            'product_launch' => [
                'subject' => "Now Available: {$productName}! 🚀",
                'body' => "Exciting news {$name}! The new product '{$productName}' has launched on JSS Marketplace.",
            ],
            'store_update' => [
                'subject' => "New from {$storeName}! 🏪",
                'body' => "Hello {$name}, {$storeName} just added new products and offers on JSS Marketplace.",
            ],
            'abandoned_cart' => [
                'subject' => "Complete your purchase on JSS Marketplace 🛒",
                'body' => "Hello {$name}, you left items in your cart totaling {$amount}. Complete your order now before items sell out!",
            ],
            'low_stock' => [
                'subject' => "Low Stock Alert: {$productName} ⚠️",
                'body' => "Urgent: '{$productName}' is low on stock ({$data['current_stock']} units remaining). Please replenish inventory.",
            ],
        ];

        $matched = $defaults[$templateKey] ?? [
            'subject' => "JSS Marketplace Notification",
            'body' => $data['message'] ?? "You have a new notification from JSS Marketplace.",
        ];

        return [
            'subject' => $this->substituteVariables($matched['subject'], $data),
            'body' => $this->substituteVariables($matched['body'], $data),
            'template_id' => null,
            'dlt_template_id' => null,
            'whatsapp_template_name' => null,
        ];
    }
}
