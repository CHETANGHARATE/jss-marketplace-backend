<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 20px 25px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table, .info-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: -0.5px;
        }
        .logo-sub {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1.5px solid #0284c7;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .info-box {
            padding: 8px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 10.5px;
            min-height: 90px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 10.5px;
        }
        .grand-total-row td {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #0284c7;
            border-bottom: 2px solid #0284c7;
            background-color: #f0f9ff;
            padding: 6px 8px;
        }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #64748b;
            text-align: center;
        }
        .terms {
            margin-top: 15px;
            padding: 8px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            font-size: 8.5px;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="logo-text">JSS<span style="color: #ea580c;">Solutions</span></div>
                <div class="logo-sub">Multi-Vendor Marketplace</div>
                <div style="margin-top: 4px; color: #475569; font-size: 10px;">
                    JSS Solutions Private Limited<br>
                    Plot No. 42, Tech Park Sector, Navi Mumbai, Maharashtra, 400705<br>
                    <strong>GSTIN:</strong> 27AABCJ9988K1Z5 | <strong>CIN:</strong> U72900MH2024PTC123456<br>
                    <strong>Email:</strong> support@jsssolutions.in | <strong>Web:</strong> www.jsssolutions.in
                </div>
            </td>
            <td style="width: 45%;" class="text-right">
                <div style="font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase;">TAX INVOICE</div>
                <table style="width: 100%; margin-top: 6px; font-size: 10.5px;">
                    <tr>
                        <td class="text-right" style="color: #64748b;"><strong>Invoice No:</strong></td>
                        <td class="text-right font-bold">{{ 'INV-' . date('Ymd', strtotime($order->created_at)) . '-' . substr($order->order_number, -5) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right" style="color: #64748b;"><strong>Invoice Date:</strong></td>
                        <td class="text-right">{{ date('d M Y, h:i A', strtotime($order->created_at)) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right" style="color: #64748b;"><strong>Order Number:</strong></td>
                        <td class="text-right font-bold" style="color: #0284c7;">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-right" style="color: #64748b;"><strong>Payment Status:</strong></td>
                        <td class="text-right">
                            <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : '' }}">
                                {{ strtoupper($order->payment_status) }} ({{ strtoupper($order->payment_method) }})
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">

    <!-- Addresses & Vendor Information -->
    <table class="info-table">
        <tr>
            <!-- Billed To -->
            <td style="width: 33.3%; padding-right: 6px;">
                <div class="section-title">Billed To (Customer)</div>
                <div class="info-box">
                    <strong>{{ $order->billing_address_snapshot['name'] ?? $order->user->name ?? 'Customer' }}</strong><br>
                    {{ $order->billing_address_snapshot['address_line_1'] ?? '' }}<br>
                    @if(!empty($order->billing_address_snapshot['address_line_2']))
                        {{ $order->billing_address_snapshot['address_line_2'] }}<br>
                    @endif
                    {{ $order->billing_address_snapshot['city'] ?? '' }}, {{ $order->billing_address_snapshot['state'] ?? '' }} - {{ $order->billing_address_snapshot['pin_code'] ?? '' }}<br>
                    <strong>Phone:</strong> {{ $order->billing_address_snapshot['phone'] ?? $order->user->mobile ?? 'N/A' }}<br>
                    @if(!empty($order->billing_address_snapshot['gst_number']))
                        <strong>Buyer GSTIN:</strong> {{ $order->billing_address_snapshot['gst_number'] }}
                    @endif
                </div>
            </td>

            <!-- Shipped To -->
            <td style="width: 33.3%; padding: 0 3px;">
                <div class="section-title">Shipped To (Delivery)</div>
                <div class="info-box">
                    <strong>{{ $order->shipping_address_snapshot['name'] ?? $order->user->name ?? 'Customer' }}</strong><br>
                    {{ $order->shipping_address_snapshot['address_line_1'] ?? '' }}<br>
                    @if(!empty($order->shipping_address_snapshot['address_line_2']))
                        {{ $order->shipping_address_snapshot['address_line_2'] }}<br>
                    @endif
                    {{ $order->shipping_address_snapshot['city'] ?? '' }}, {{ $order->shipping_address_snapshot['state'] ?? '' }} - {{ $order->shipping_address_snapshot['pin_code'] ?? '' }}<br>
                    <strong>Phone:</strong> {{ $order->shipping_address_snapshot['phone'] ?? $order->user->mobile ?? 'N/A' }}<br>
                    <strong>Type:</strong> {{ ucfirst($order->shipping_address_snapshot['type'] ?? 'Home') }}
                </div>
            </td>

            <!-- Sold By -->
            <td style="width: 33.3%; padding-left: 6px;">
                <div class="section-title">Sold By (Seller)</div>
                <div class="info-box">
                    @php
                        $primaryItem = $order->items->first();
                        $sellerStore = $primaryItem && $primaryItem->product && $primaryItem->product->sellerStore ? $primaryItem->product->sellerStore : null;
                    @endphp
                    <strong>{{ $sellerStore->store_name ?? 'JSS Authorized Marketplace Vendor' }}</strong><br>
                    {{ $sellerStore->city ?? 'Navi Mumbai' }}, {{ $sellerStore->state ?? 'Maharashtra' }}<br>
                    <strong>GSTIN:</strong> {{ $sellerStore->gst_number ?? '27AABCJ9988K1Z5' }}<br>
                    <strong>PAN:</strong> {{ $sellerStore->pan_number ?? 'AABCJ9988K' }}<br>
                    <strong>Fulfillment:</strong> Direct from Source
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 38%;">Product Description</th>
                <th style="width: 12%;" class="text-center">HSN / SKU</th>
                <th style="width: 8%;" class="text-center">Qty</th>
                <th style="width: 12%;" class="text-right">Unit Price</th>
                <th style="width: 10%;" class="text-right">GST Rate</th>
                <th style="width: 15%;" class="text-right">Total (INR)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $taxableSum = 0;
                $cgstSum = 0;
                $sgstSum = 0;
            @endphp
            @foreach($order->items as $index => $item)
                @php
                    $isCancelled = ($item->status === 'cancelled');
                    $itemTaxRate = 18.0; // Standard GST benchmark
                    $itemTotal = (float) $item->subtotal;
                    $itemTaxable = $itemTotal / (1 + ($itemTaxRate / 100));
                    $itemGstAmount = $itemTotal - $itemTaxable;
                    if (!$isCancelled) {
                        $taxableSum += $itemTaxable;
                        $cgstSum += ($itemGstAmount / 2);
                        $sgstSum += ($itemGstAmount / 2);
                    }
                @endphp
                <tr style="{{ $isCancelled ? 'opacity: 0.5; background-color: #fee2e2;' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($isCancelled)
                            <span style="color: #b91c1c; font-weight: bold; font-size: 8.5px; margin-left: 4px;">[CANCELLED]</span>
                        @endif
                        <div style="font-size: 8.5px; color: #64748b;">Sold by: {{ $item->product->sellerStore->store_name ?? 'JSS Vendor' }}</div>
                    </td>
                    <td class="text-center" style="font-size: 9px; color: #475569;">
                        {{ $item->product_sku ?? 'SKU-'.$item->product_id }}<br>
                        <span style="font-size: 8px; color: #94a3b8;">HSN: 8517</span>
                    </td>
                    <td class="text-center font-bold">{{ $item->quantity }}</td>
                    <td class="text-right">₹{{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="text-right" style="font-size: 9px;">
                        18%<br>
                        <span style="font-size: 7.5px; color: #64748b;">(CGST 9% + SGST 9%)</span>
                    </td>
                    <td class="text-right font-bold">
                        @if($isCancelled)
                            <strike>₹{{ number_format($itemTotal, 2) }}</strike><br>
                            <span style="color: #dc2626; font-size: 8px;">₹0.00</span>
                        @else
                            ₹{{ number_format($itemTotal, 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals & Tax Calculation Breakdown -->
    <table style="width: 100%;">
        <tr>
            <!-- Left: Notes & Tax Summary -->
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-title">GST Tax Summary</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; border: 1px solid #e2e8f0;">
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td style="padding: 4px; border: 1px solid #cbd5e1;">Tax Type</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">Taxable Value</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">Rate</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">Tax Amount</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;">Central GST (CGST)</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">₹{{ number_format($taxableSum, 2) }}</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">9.0%</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">₹{{ number_format($cgstSum, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;">State GST (SGST)</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">₹{{ number_format($taxableSum, 2) }}</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">9.0%</td>
                        <td style="padding: 4px; border: 1px solid #e2e8f0;" class="text-right">₹{{ number_format($sgstSum, 2) }}</td>
                    </tr>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td style="padding: 4px; border: 1px solid #cbd5e1;">Total GST Assessed</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">₹{{ number_format($taxableSum, 2) }}</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">18.0%</td>
                        <td style="padding: 4px; border: 1px solid #cbd5e1;" class="text-right">₹{{ number_format($cgstSum + $sgstSum, 2) }}</td>
                    </tr>
                </table>

                <div class="terms">
                    <strong>Declaration:</strong> We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct. Goods once sold are covered under JSS Marketplace 7-Day Replacement & Return Policy.
                </div>
            </td>

            <!-- Right: Order Financial Summary -->
            <td style="width: 48%; vertical-align: top;">
                <table class="totals-table" style="width: 100%;">
                    <tr>
                        <td style="color: #64748b;">Subtotal (Inclusive of GST):</td>
                        <td class="text-right font-bold">₹{{ number_format((float)$order->subtotal, 2) }}</td>
                    </tr>
                    @if((float)$order->discount_amount > 0)
                        <tr>
                            <td style="color: #16a34a;">Coupon / Promotional Discount:</td>
                            <td class="text-right font-bold" style="color: #16a34a;">- ₹{{ number_format((float)$order->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if((float)$order->loyalty_discount_amount > 0)
                        <tr>
                            <td style="color: #d97706;">JSS Coins Redeemed ({{ $order->loyalty_points_redeemed }} pts):</td>
                            <td class="text-right font-bold" style="color: #d97706;">- ₹{{ number_format((float)$order->loyalty_discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #64748b;">Shipping & Delivery Charges:</td>
                        <td class="text-right font-bold">
                            @if((float)$order->shipping_amount > 0)
                                ₹{{ number_format((float)$order->shipping_amount, 2) }}
                            @else
                                <span style="color: #16a34a;">FREE</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="grand-total-row">
                        <td>Total Amount Payable:</td>
                        <td class="text-right">₹{{ number_format((float)$order->total_amount, 2) }}</td>
                    </tr>
                </table>

                <div style="margin-top: 15px; text-align: right; padding-right: 8px;">
                    <div style="font-size: 9px; color: #64748b;">For JSS Solutions Private Limited</div>
                    <div style="font-family: cursive; font-size: 14px; color: #1e3a8a; margin: 6px 0;">Authorized Signatory</div>
                    <div style="font-size: 8px; color: #94a3b8;">This is a computer-generated tax invoice and requires no physical signature.</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        Thank you for shopping on JSS Marketplace! For support, visit www.jsssolutions.in/help-center or call toll-free +91 1800-JSS-SHOP.
    </div>

</body>
</html>
