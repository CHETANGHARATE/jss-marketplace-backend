<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'JSSSolutions Marketplace' }}</title>
    <style>
        /* Base Reset */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
        }
        /* Layout Container */
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        /* Header */
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 32px 24px;
            text-align: center;
        }
        .header-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .header-slogan {
            margin: 6px 0 0 0;
            color: #38bdf8;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        /* Main Body */
        .content {
            padding: 36px 32px;
        }
        /* Status Badge */
        .badge-wrapper {
            margin-bottom: 20px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 9999px;
        }
        .badge-success {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .badge-info {
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .badge-warning {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .badge-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        /* Headings */
        .heading {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 16px 0;
            letter-spacing: -0.3px;
        }
        .paragraph {
            font-size: 15px;
            color: #334155;
            margin: 0 0 24px 0;
            line-height: 1.7;
        }
        /* Summary Card */
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 14px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
        }
        .meta-label {
            color: #64748b;
            font-weight: 500;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }
        /* Items Table */
        .items-table {
            width: 100%;
            margin-bottom: 24px;
        }
        .items-table th {
            text-align: left;
            padding: 10px 12px;
            background-color: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 6px;
        }
        .items-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #1e293b;
        }
        .item-name {
            font-weight: 600;
            color: #0f172a;
        }
        .item-sku {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        /* Totals */
        .totals-table {
            width: 100%;
            margin-bottom: 28px;
        }
        .totals-table td {
            padding: 6px 12px;
            font-size: 14px;
        }
        .total-row {
            border-top: 2px solid #e2e8f0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        /* CTA Button */
        .cta-wrapper {
            text-align: center;
            margin: 32px 0 16px 0;
        }
        .btn-primary {
            display: inline-block;
            background-color: #0284c7;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.25);
        }
        /* Footer */
        .footer {
            background-color: #f8fafc;
            padding: 32px 24px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header with Official Marketplace Branding -->
            <div class="header">
                <h1 class="header-title">JSSSolutions Marketplace</h1>
                <p class="header-slogan">India Shops Here</p>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Status Badge (Optional) -->
                @if(!empty($data['status_badge']))
                    @php
                        $badgeClass = match(strtolower($data['status_badge_type'] ?? '')) {
                            'success', 'confirmed', 'paid' => 'badge-success',
                            'warning', 'pending' => 'badge-warning',
                            'danger', 'failed', 'cancelled' => 'badge-danger',
                            default => 'badge-info'
                        };
                    @endphp
                    <div class="badge-wrapper">
                        <span class="badge {{ $badgeClass }}">{{ $data['status_badge'] }}</span>
                    </div>
                @endif

                <!-- Heading -->
                <h2 class="heading">{{ $subject ?? 'Order Update' }}</h2>

                <!-- Main Message Body -->
                <div class="paragraph">
                    {!! nl2br(e($body ?? '')) !!}
                </div>

                <!-- Order Details Card (If Order Data Available) -->
                @if(!empty($data['order_number']))
                    <div class="card">
                        <div class="card-title">Order Information</div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 4px 0; color: #64748b; font-size: 13px;">Order Number:</td>
                                <td style="padding: 4px 0; color: #0f172a; font-weight: 700; text-align: right; font-size: 13px;">#{{ $data['order_number'] }}</td>
                            </tr>
                            @if(!empty($data['order_date']))
                                <tr>
                                    <td style="padding: 4px 0; color: #64748b; font-size: 13px;">Order Date:</td>
                                    <td style="padding: 4px 0; color: #0f172a; font-weight: 600; text-align: right; font-size: 13px;">{{ $data['order_date'] }}</td>
                                </tr>
                            @endif
                            @if(!empty($data['payment_method']))
                                <tr>
                                    <td style="padding: 4px 0; color: #64748b; font-size: 13px;">Payment Method:</td>
                                    <td style="padding: 4px 0; color: #0f172a; font-weight: 600; text-align: right; font-size: 13px;">
                                        {{ strtoupper($data['payment_method']) === 'COD' ? 'Cash on Delivery (COD)' : ucfirst($data['payment_method']) }}
                                    </td>
                                </tr>
                            @endif
                            @if(!empty($data['payment_status']))
                                <tr>
                                    <td style="padding: 4px 0; color: #64748b; font-size: 13px;">Payment Status:</td>
                                    <td style="padding: 4px 0; color: {{ strtolower($data['payment_status']) === 'paid' ? '#059669' : '#d97706' }}; font-weight: 700; text-align: right; font-size: 13px;">
                                        {{ ucfirst($data['payment_status']) }}
                                    </td>
                                </tr>
                            @endif
                            @if(!empty($data['tracking_number']) && $data['tracking_number'] !== 'N/A')
                                <tr>
                                    <td style="padding: 4px 0; color: #64748b; font-size: 13px;">Tracking Number:</td>
                                    <td style="padding: 4px 0; color: #0284c7; font-weight: 700; text-align: right; font-size: 13px;">{{ $data['tracking_number'] }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                @endif

                <!-- Shipping Address Card (If Available) -->
                @if(!empty($data['shipping_address_text']))
                    <div class="card" style="margin-top: -12px;">
                        <div class="card-title">Delivery Address</div>
                        <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.6;">
                            {!! nl2br(e($data['shipping_address_text'])) !!}
                        </p>
                    </div>
                @endif

                <!-- Itemized Order Items (If Available) -->
                @if(!empty($data['items']) && is_array($data['items']) && count($data['items']) > 0)
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="item-name">{{ $item['name'] ?? $item['product_name'] ?? 'Product' }}</div>
                                        @if(!empty($item['sku']))
                                            <div class="item-sku">SKU: {{ $item['sku'] }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align: center; color: #64748b;">{{ $item['quantity'] ?? 1 }}</td>
                                    <td style="text-align: right; color: #64748b;">₹{{ number_format((float)($item['unit_price'] ?? 0), 2) }}</td>
                                    <td style="text-align: right; font-weight: 700;">₹{{ number_format((float)($item['total_price'] ?? ($item['subtotal'] ?? 0)), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Financial Breakdown -->
                @if(!empty($data['amount']) || !empty($data['total']))
                    <table class="totals-table">
                        @if(!empty($data['subtotal']))
                            <tr>
                                <td style="color: #64748b;">Subtotal:</td>
                                <td style="text-align: right; font-weight: 600;">₹{{ number_format((float)$data['subtotal'], 2) }}</td>
                            </tr>
                        @endif
                        @if(isset($data['tax']) && (float)$data['tax'] > 0)
                            <tr>
                                <td style="color: #64748b;">Estimated GST (18%):</td>
                                <td style="text-align: right; font-weight: 600;">₹{{ number_format((float)$data['tax'], 2) }}</td>
                            </tr>
                        @endif
                        @if(isset($data['shipping']))
                            <tr>
                                <td style="color: #64748b;">Shipping Fee:</td>
                                <td style="text-align: right; font-weight: 600;">
                                    {{ (float)$data['shipping'] == 0 ? 'FREE' : '₹' . number_format((float)$data['shipping'], 2) }}
                                </td>
                            </tr>
                        @endif
                        @if(!empty($data['discount']) && (float)$data['discount'] > 0)
                            <tr>
                                <td style="color: #059669;">Discounts / JSS Coins:</td>
                                <td style="text-align: right; font-weight: 700; color: #059669;">-₹{{ number_format((float)$data['discount'], 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td style="padding-top: 10px; font-weight: 800;">Total Amount:</td>
                            <td style="padding-top: 10px; text-align: right; font-weight: 800; color: #0284c7; font-size: 18px;">
                                ₹{{ number_format((float)($data['amount'] ?? $data['total'] ?? 0), 2) }}
                            </td>
                        </tr>
                    </table>
                @endif

                <!-- Call to Action Button -->
                @php
                    $ctaUrl = $data['cta_url'] ?? (!empty($data['order_number']) ? 'https://jsssolutions.in/orders/' . $data['order_number'] : 'https://jsssolutions.in');
                    $ctaText = $data['cta_text'] ?? (!empty($data['order_number']) ? 'View Order Details' : 'Visit Marketplace');
                @endphp
                <div class="cta-wrapper">
                    <a href="{{ $ctaUrl }}" class="btn-primary" target="_blank">{{ $ctaText }}</a>
                </div>
            </div>

            <!-- Footer with Platform Details -->
            <div class="footer">
                <p style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">JSSSolutions Marketplace — India Shops Here</p>
                <p>Need help with your order? Visit our <a href="https://jsssolutions.in/contact" target="_blank">Help Center</a>, call Customer Care at <a href="tel:+919996669884">+91 99966 69884</a>, or email <a href="mailto:support@jsssolutions.in">support@jsssolutions.in</a></p>
                <p style="margin-top: 12px; color: #94a3b8;">&copy; {{ date('Y') }} JSSSolutions Marketplace. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
