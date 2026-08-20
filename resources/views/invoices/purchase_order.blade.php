<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PURCHASE ORDER - {{ $po->po_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            color: #ea580c;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
        }
        .two-column {
            width: 100%;
            margin-bottom: 20px;
        }
        .two-column td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-right: 10px;
        }
        .box-title {
            font-weight: bold;
            font-size: 12px;
            color: #0f172a;
            margin-bottom: 6px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px;
            text-align: left;
        }
        table.items-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px;
            font-size: 11px;
        }
        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .totals-table tr.grand-total {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }
        .terms-box {
            border: 1px dashed #94a3b8;
            padding: 10px;
            border-radius: 6px;
            font-size: 10px;
            color: #475569;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">PURCHASE ORDER</div>
                    <div class="subtitle">Official B2B Commercial Purchase Order</div>
                </td>
                <td style="text-align: right;">
                    <strong style="font-size: 14px;">PO #: {{ $po->po_number }}</strong><br>
                    <span>Date: {{ $po->created_at->format('d-M-Y') }}</span><br>
                    <span>Status: <strong style="text-transform: uppercase; color: #ea580c;">{{ $po->status }}</strong></span>
                </td>
            </tr>
        </table>
    </div>

    <table class="two-column">
        <tr>
            <td>
                <div class="box">
                    <div class="box-title">Buyer (Purchaser) Details</div>
                    <strong>{{ $po->buyer->businessAccount->legal_business_name ?? $po->buyer->name }}</strong><br>
                    @if(!empty($po->buyer->businessAccount->gstin))
                        GSTIN: <strong>{{ $po->buyer->businessAccount->gstin }}</strong><br>
                    @endif
                    @if(!empty($po->buyer->businessAccount->pan))
                        PAN: {{ $po->buyer->businessAccount->pan }}<br>
                    @endif
                    Email: {{ $po->buyer->email }} | Phone: {{ $po->buyer->businessAccount->business_phone ?? $po->buyer->mobile }}<br>
                    Address: {{ $po->buyer->businessAccount->registered_address ?? 'Not Specified' }}
                </div>
            </td>
            <td>
                <div class="box" style="margin-right: 0; margin-left: 10px;">
                    <div class="box-title">Vendor / Supplier Details</div>
                    <strong>{{ $po->seller->vendorStore->store_name ?? $po->seller->name }}</strong><br>
                    @if(!empty($po->seller->vendorStore->gstin))
                        GSTIN: <strong>{{ $po->seller->vendorStore->gstin }}</strong><br>
                    @endif
                    Email: {{ $po->seller->email }} | Phone: {{ $po->seller->vendorStore->store_phone ?? $po->seller->mobile }}<br>
                    Payment Terms: <strong>{{ $po->payment_terms }}</strong><br>
                    Delivery Terms: <strong>{{ $po->delivery_terms }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Unit Price (₹)</th>
                <th style="width: 20%; text-align: right;">Total Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->sku)<br><small style="color: #64748b;">SKU: {{ $item->sku }}</small>@endif
                </td>
                <td style="text-align: center;">{{ number_format($item->quantity) }}</td>
                <td style="text-align: right;">₹{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">₹{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td style="text-align: right;">₹{{ number_format($po->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Tax (GST Estimated):</td>
            <td style="text-align: right;">₹{{ number_format($po->tax_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Shipping & Freight:</td>
            <td style="text-align: right;">₹{{ number_format($po->shipping_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td><strong>TOTAL (INR):</strong></td>
            <td style="text-align: right;"><strong>₹{{ number_format($po->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="terms-box">
        <strong>Terms & Conditions:</strong><br>
        1. This Purchase Order constitutes a binding commercial agreement upon acceptance by the Vendor.<br>
        2. All goods must strictly adhere to specifications, packaging, and dispatch timelines agreed herein.<br>
        3. Payment shall be settled in accordance with the stipulated payment terms: <strong>{{ $po->payment_terms }}</strong>.<br>
        4. Disputes subject to exclusive jurisdiction of designated commercial courts.
    </div>

    <div class="footer">
        Generated electronically via JSS Solutions Marketplace B2B Commerce Engine | https://jsssolutions.in
    </div>
</body>
</html>
