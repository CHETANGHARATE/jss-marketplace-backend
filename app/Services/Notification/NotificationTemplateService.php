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
        $trackingNumber = $data['tracking_number'] ?? null;
        $currentStock = $data['current_stock'] ?? 0;
        $reason = $data['reason'] ?? $data['failure_reason'] ?? '';
        $returnNum = $data['return_number'] ?? $orderNum;

        // Multi-Language Mappings
        if ($language === 'hi') {
            $matched = match ($templateKey) {
                'order_placed' => [
                    'subject' => "ऑर्डर प्राप्त हुआ: #{$orderNum} — जेएसएस मार्केटप्लेस",
                    'body' => "नमस्ते {$name},\n\nआपका ऑर्डर #{$orderNum} कुल {$amount} के लिए सफलतापूर्वक प्राप्त हो गया है। हमारी टीम इसे तैयार कर रही है।",
                ],
                'order_confirmed' => [
                    'subject' => "ऑर्डर कन्फर्म हुआ: #{$orderNum} — भुगतान सफल 🎉",
                    'body' => "नमस्ते {$name},\n\nआपका ऑनलाइन भुगतान सफलतापूर्वक प्राप्त हो गया है और आपका ऑर्डर #{$orderNum} कुल राशि {$amount} के लिए कन्फर्म कर दिया गया है।",
                ],
                'order_payment_pending' => [
                    'subject' => "भुगतान प्रतीक्षित: ऑर्डर #{$orderNum}",
                    'body' => "नमस्ते {$name},\n\nआपके ऑर्डर #{$orderNum} का ऑनलाइन भुगतान अभी प्रतीक्षित है। कृपया अपनी खरीदारी पूरी करने के लिए भुगतान प्रक्रिया संपन्न करें।",
                ],
                'order_payment_failed' => [
                    'subject' => "भुगतान विफल: ऑर्डर #{$orderNum}",
                    'body' => "नमस्ते {$name},\n\nआपके ऑर्डर #{$orderNum} के लिए भुगतान पूरा नहीं हो सका। कृपया पुनः प्रयास करें।",
                ],
                'order_shipped' => [
                    'subject' => "ऑर्डर भेज दिया गया है 🚚: #{$orderNum}",
                    'body' => $trackingNumber && $trackingNumber !== 'N/A'
                        ? "नमस्ते {$name},\n\nआपका ऑर्डर #{$orderNum} भेज दिया गया है। ट्रैकिंग नंबर: {$trackingNumber}."
                        : "नमस्ते {$name},\n\nआपका ऑर्डर #{$orderNum} भेज दिया गया है और रास्ते में है।",
                ],
                'order_delivered' => [
                    'subject' => "ऑर्डर डिलीवर हो गया! 🎁: #{$orderNum}",
                    'body' => "नमस्ते {$name},\n\nआपका ऑर्डर #{$orderNum} सफलतापूर्वक डिलीवर कर दिया गया है। जेएसएस मार्केटप्लेस पर खरीदारी के लिए धन्यवाद!",
                ],
                'order_cancelled' => [
                    'subject' => "ऑर्डर रद्द किया गया: #{$orderNum}",
                    'body' => "नमस्ते {$name},\n\nआपका ऑर्डर #{$orderNum} रद्द कर दिया गया है।" . ($reason ? "\nकारण: {$reason}" : ""),
                ],
                'refund_completed' => [
                    'subject' => "रिफंड पूरा हुआ 💰: #{$returnNum}",
                    'body' => "नमस्ते {$name},\n\nआपके रिटर्न #{$returnNum} के लिए {$amount} का रिफंड सफलतापूर्वक प्रोसेस कर दिया गया है।",
                ],
                default => [
                    'subject' => "जेएसएस मार्केटप्लेस सूचना",
                    'body' => $data['message'] ?? "आपको जेएसएस मार्केटप्लेस से एक नया संदेश प्राप्त हुआ है।",
                ],
            };
        } elseif ($language === 'mr') {
            $matched = match ($templateKey) {
                'order_placed' => [
                    'subject' => "ऑर्डर नोंदवला: #{$orderNum} — जेएसएस मार्केटप्लेस",
                    'body' => "नमस्कार {$name},\n\nतुमचा ऑर्डर #{$orderNum} एकूण {$amount} साठी यशस्वीरीत्या नोंदवला गेला आहे. आमची टीम त्याची तयारी करत आहे.",
                ],
                'order_confirmed' => [
                    'subject' => "ऑर्डर कन्फर्म झाला: #{$orderNum} — पेमेंट यशस्वी 🎉",
                    'body' => "नमस्कार {$name},\n\nतुमचे ऑनलाइन पेमेंट यशस्वी झाले असून ऑर्डर #{$orderNum} एकूण रक्कम {$amount} साठी कन्फर्म झाली आहे.",
                ],
                'order_payment_pending' => [
                    'subject' => "पेमेंट बाकी आहे: ऑर्डर #{$orderNum}",
                    'body' => "नमस्कार {$name},\n\nतुमच्या ऑर्डर #{$orderNum} चे पेमेंट अद्याप अपूर्ण आहे. कृपया खरेदी पूर्ण करण्यासाठी पेमेंट पूर्ण करा.",
                ],
                'order_payment_failed' => [
                    'subject' => "पेमेंट अयशस्वी: ऑर्डर #{$orderNum}",
                    'body' => "नमस्कार {$name},\n\nतुमच्या ऑर्डर #{$orderNum} चे पेमेंट पूर्ण होऊ शकले नाही. कृपया पुन्हा प्रयत्न करा.",
                ],
                'order_shipped' => [
                    'subject' => "ऑर्डर पाठवला आहे 🚚: #{$orderNum}",
                    'body' => $trackingNumber && $trackingNumber !== 'N/A'
                        ? "नमस्कार {$name},\n\nतुमचा ऑर्डर #{$orderNum} पाठवला गेला आहे. ट्रॅकिंग नंबर: {$trackingNumber}."
                        : "नमस्कार {$name},\n\nतुमचा ऑर्डर #{$orderNum} पाठवला गेला असून तो लवकरच पोहोचेल.",
                ],
                'order_delivered' => [
                    'subject' => "ऑर्डर वितरित झाला! 🎁: #{$orderNum}",
                    'body' => "नमस्कार {$name},\n\nतुमचा ऑर्डर #{$orderNum} यशस्वीरीत्या वितरित झाला आहे. जेएसएस मार्केटप्लेसवर खरेदी केल्याबद्दल धन्यवाद!",
                ],
                'order_cancelled' => [
                    'subject' => "ऑर्डर रद्द करण्यात आला: #{$orderNum}",
                    'body' => "नमस्कार {$name},\n\nतुमचा ऑर्डर #{$orderNum} रद्द करण्यात आला आहे।" . ($reason ? "\nकारण: {$reason}" : ""),
                ],
                'refund_completed' => [
                    'subject' => "परतावा (रिफंड) पूर्ण झाला 💰: #{$returnNum}",
                    'body' => "नमस्कार {$name},\n\nतुमच्या परताव्यासाठी {$amount} चा रिफंड यशस्वीरीत्या खात्यात जमा करण्यात आला आहे.",
                ],
                default => [
                    'subject' => "जेएसएस मार्केटप्लेस सूचना",
                    'body' => $data['message'] ?? "आपल्याला जेएसएस मार्केटप्लेस कडून संदेश आला आहे.",
                ],
            };
        } else {
            // Default English
            $matched = match ($templateKey) {
                'order_placed' => [
                    'subject' => "Order Received: #{$orderNum} — JSS Marketplace",
                    'body' => "Hello {$name},\n\nThank you for shopping with JSSSolutions Marketplace! Your order #{$orderNum} totaling {$amount} has been received and is being processed.",
                ],
                'order_confirmed' => [
                    'subject' => "Order Confirmed — Payment Successful: #{$orderNum} 🎉",
                    'body' => "Hello {$name},\n\nYour payment has been successfully verified! Your order #{$orderNum} for {$amount} is confirmed and is now being prepared for fulfillment.",
                ],
                'order_payment_pending' => [
                    'subject' => "Payment Pending — Order #{$orderNum}",
                    'body' => "Hello {$name},\n\nYour order #{$orderNum} totaling {$amount} is awaiting payment completion. Please complete your transaction to confirm your purchase.",
                ],
                'order_payment_failed' => [
                    'subject' => "Payment Failed — Order #{$orderNum}",
                    'body' => "Hello {$name},\n\nWe were unable to process payment for order #{$orderNum}. " . ($reason ? "Reason: {$reason}\n" : "") . "\nYou can safely retry your payment from your orders dashboard.",
                ],
                'order_shipped' => [
                    'subject' => "Your Order Has Shipped 🚚: #{$orderNum}",
                    'body' => $trackingNumber && $trackingNumber !== 'N/A'
                        ? "Hello {$name},\n\nGreat news! Your order #{$orderNum} has been dispatched. Your tracking number is {$trackingNumber}."
                        : "Hello {$name},\n\nGreat news! Your order #{$orderNum} has been dispatched and is on its way to you.",
                ],
                'order_delivered' => [
                    'subject' => "Order Delivered! 🎁: #{$orderNum}",
                    'body' => "Hello {$name},\n\nYour package for order #{$orderNum} has been successfully delivered. Thank you for shopping with JSSSolutions Marketplace!",
                ],
                'order_cancelled' => [
                    'subject' => "Order Cancelled — #{$orderNum}",
                    'body' => "Hello {$name},\n\nYour order #{$orderNum} has been cancelled." . ($reason ? "\nReason: {$reason}" : "") . "\nIf payment was already deducted, your refund has been automatically initiated.",
                ],
                'item_cancelled' => [
                    'subject' => "Item Cancelled — Order #{$orderNum}",
                    'body' => "Hello {$name},\n\nAn item ('{$productName}') from order #{$orderNum} has been cancelled." . ($reason ? "\nReason: {$reason}" : ""),
                ],
                'refund_initiated' => [
                    'subject' => "Refund Initiated — Order #{$orderNum}",
                    'body' => "Hello {$name},\n\nA refund of {$amount} for order/return #{$returnNum} has been initiated and is being processed by our payments team.",
                ],
                'refund_completed' => [
                    'subject' => "Refund Completed 💰: #{$orderNum}",
                    'body' => "Hello {$name},\n\nYour refund of {$amount} for order/return #{$returnNum} has been successfully completed. Funds should reflect in your original payment method in 3-5 business days.",
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
                    'body' => "Urgent: '{$productName}' is low on stock ({$currentStock} units remaining). Please replenish inventory.",
                ],
                default => [
                    'subject' => "JSS Marketplace Notification",
                    'body' => $data['message'] ?? "You have a new notification from JSS Marketplace.",
                ],
            };
        }

        return [
            'subject' => $this->substituteVariables($matched['subject'], $data),
            'body' => $this->substituteVariables($matched['body'], $data),
            'template_id' => null,
            'dlt_template_id' => null,
            'whatsapp_template_name' => null,
        ];
    }
}
