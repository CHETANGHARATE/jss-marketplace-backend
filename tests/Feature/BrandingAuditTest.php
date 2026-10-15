<?php

namespace Tests\Feature;

use App\Services\Notification\NotificationTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class BrandingAuditTest extends TestCase
{
    /**
     * Test brand configuration values are loaded correctly.
     */
    public function test_brand_configuration_is_present(): void
    {
        $this->assertEquals('JDS Mart', config('brand.name'));
        $this->assertEquals('India Shops Here', config('brand.tagline'));
        $this->assertEquals('JDS Mart Private Limited', config('brand.legal_name'));
        $this->assertEquals('+91 99966 69884', config('brand.support_phone'));
        $this->assertEquals('support@jsssolutions.in', config('brand.support_email'));
    }

    /**
     * Test notification template fallbacks use JDS Mart branding and no legacy strings.
     */
    public function test_notification_template_fallbacks_use_jds_mart(): void
    {
        $service = new NotificationTemplateService();
        $events = [
            'order_placed',
            'order_confirmed',
            'order_shipped',
            'order_delivered',
            'order_cancelled',
            'order_payment_pending',
            'order_payment_failed',
            'refund_completed',
            'abandoned_cart',
        ];

        foreach ($events as $event) {
            $tpl = $service->getBuiltInFallback($event, 'email', 'en', [
                'name' => 'Rahul Sharma',
                'order_number' => 'ORD-12345',
                'amount' => 1999,
                'product_name' => 'Test Product',
                'new_price' => 999,
                'old_price' => 1299,
            ]);

            $subj = $tpl['subject'] ?? '';
            $body = $tpl['body'] ?? '';

            $this->assertStringNotContainsString('JSSSolutions', $body, "Found legacy branding in {$event} body");
            $this->assertStringNotContainsString('JSS Marketplace', $body, "Found legacy branding in {$event} body");
            $this->assertStringNotContainsString('JSS Marketplace', $subj, "Found legacy branding in {$event} subject");
        }
    }

    /**
     * Test transactional email layouts render JDS Mart and India Shops Here.
     */
    public function test_transactional_email_layout_rendering(): void
    {
        $sampleData = [
            'name' => 'Rahul Sharma',
            'order_number' => 'ORD-20261016-001',
            'order_date' => '16 Oct 2026, 10:30 AM',
            'amount' => 1999.00,
            'total' => 1999.00,
            'subtotal' => 1800.00,
            'tax' => 199.00,
            'shipping' => 0.00,
            'discount' => 0.00,
            'payment_method' => 'Razorpay / Prepaid',
            'payment_status' => 'paid',
            'shipping_address_text' => "Rahul Sharma\nFlat 101, Galaxy Heights\nBandra West, Mumbai 400050",
            'items' => [
                [
                    'name' => 'Wireless Bluetooth Earbuds',
                    'sku' => 'EAR-001',
                    'quantity' => 1,
                    'unit_price' => 1999.00,
                    'total_price' => 1999.00,
                ],
            ],
            'status_badge' => 'Order Confirmed',
            'status_badge_type' => 'success',
            'cta_text' => 'Track Your Order',
            'cta_url' => 'https://jsssolutions.in/track/ORD-20261016-001',
        ];

        $service = new NotificationTemplateService();
        $tpl = $service->getBuiltInFallback('order_confirmed', 'email', 'en', [
            'name' => 'Rahul Sharma',
            'order_number' => 'ORD-20261016-001',
            'amount' => 1999,
        ]);

        $html = view('emails.marketplace_layout', [
            'subject' => 'Order Confirmed — Payment Successful: #ORD-20261016-001 🎉',
            'body' => $tpl['body'],
            'data' => $sampleData,
        ])->render();

        $plain = view('emails.marketplace_layout_plain', [
            'subject' => 'Order Confirmed — Payment Successful: #ORD-20261016-001 🎉',
            'body' => $tpl['body'],
            'data' => $sampleData,
        ])->render();

        $this->assertStringContainsString('JDS Mart', $html);
        $this->assertStringContainsString('India Shops Here', $html);
        $this->assertStringContainsString('Thank you for choosing JDS Mart!', $html);
        $this->assertStringNotContainsString('JSSSolutions', $html);
        $this->assertStringNotContainsString('JSS Marketplace', $html);

        $this->assertStringContainsString('JDS MART — INDIA SHOPS HERE', $plain);
    }

    /**
     * Test GST Tax invoice template and PDF generation.
     */
    public function test_gst_invoice_rendering_and_pdf_compilation(): void
    {
        $mockOrder = new \stdClass();
        $mockOrder->order_number = 'ORD-20261016-TEST';
        $mockOrder->created_at = '2026-10-16 10:00:00';
        $mockOrder->payment_status = 'paid';
        $mockOrder->payment_method = 'online';
        $mockOrder->subtotal = 2000.00;
        $mockOrder->discount_amount = 200.00;
        $mockOrder->loyalty_discount_amount = 50.00;
        $mockOrder->loyalty_points_redeemed = 50;
        $mockOrder->shipping_amount = 0.00;
        $mockOrder->total_amount = 1750.00;
        $mockOrder->shipping_address_snapshot = [
            'name' => 'Amit Sharma',
            'address_line_1' => 'Flat 401, Sea View Tower',
            'address_line_2' => 'Worli Sea Face',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pin_code' => '400018',
            'phone' => '+919876543210',
            'type' => 'Home',
        ];
        $mockOrder->billing_address_snapshot = $mockOrder->shipping_address_snapshot;

        $mockItem = new \stdClass();
        $mockItem->product_name = 'Premium Organic Honey 500g';
        $mockItem->product_id = 101;
        $mockItem->seller_id = 5;
        $mockItem->quantity = 2;
        $mockItem->unit_price = 1000.00;
        $mockItem->subtotal = 2000.00;
        $mockItem->status = 'delivered';
        $mockItem->product_sku = 'HONEY-500G';

        $mockOrder->items = collect([$mockItem]);
        $mockOrder->user = (object)['name' => 'Amit Sharma', 'phone' => '+919876543210'];

        $invoiceHtml = view('invoices.gst_invoice', [
            'order' => $mockOrder,
            'vendorStores' => collect(),
        ])->render();

        $this->assertStringContainsString('JDS Mart', $invoiceHtml);
        $this->assertStringContainsString('JDS Mart Private Limited', $invoiceHtml);
        $this->assertStringContainsString('27AABCJ9988K1Z5', $invoiceHtml);
        $this->assertStringContainsString('U72900MH2024PTC123456', $invoiceHtml);
        $this->assertStringContainsString('+91 99966 69884', $invoiceHtml);
        $this->assertStringContainsString('JDS Authorized Marketplace Vendor', $invoiceHtml);
        $this->assertStringNotContainsString('JSS Authorized', $invoiceHtml);
        $this->assertStringNotContainsString('JSSSolutions Marketplace', $invoiceHtml);
        $this->assertStringNotContainsString('JSS Marketplace', $invoiceHtml);

        // PDF Generation
        $pdf = Pdf::loadView('invoices.gst_invoice', [
            'order' => $mockOrder,
            'vendorStores' => collect(),
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdfOutput = $pdf->output();

        $this->assertStringStartsWith('%PDF', $pdfOutput);
    }
}
