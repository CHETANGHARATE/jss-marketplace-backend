<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PROFORMA INVOICE - {{ $pi->proforma_number }}</title>
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
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
        }
        .proforma-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            margin-top: 4px;
            text-transform: uppercase;
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
            background-color: #1e293b;
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
            background-color: #eff6ff;
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #2563eb;
            border-bottom: 2px solid #2563eb;
        }
        .bank-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 12px;
            border-radius: 6px;
            font-size: 11px;
            color: #166534;
            margin-top: 15px;
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
                    <div class="title">PROFORMA INVOICE</div>
                    <div class="subtitle">Official Pre-Payment Quotation Document</div>
                    <div class="proforma-badge">NOT A TAX INVOICE — FOR CUSTOMS / PRE-PAYMENT ONLY</div>
                </td>
                <td style="text-align: right;">
                    <strong style="font-size: 14px;">PI #: {{ $pi->proforma_number }}</strong><br>
                    <span>Date: {{ $pi->created_at->format('d-M-Y') }}</span><br>
                    @if($pi->valid_until)<span>Valid Until: {{ $pi->valid_until->format('d-M-Y') }}</span><br>@endif
                    <span>Status: <strong style="text-transform: uppercase; color: #2563eb;">{{ $pi->status }}</strong></span>
                </td>
            </tr>
        </table>
    </div>

    <table class="two-column">
        <tr>
            <td>
                <div class="box">
                    <div class="box-title">Seller / Remit To</div>
                    <strong>{{ $pi->seller->vendorStore->store_name ?? $pi->seller->name }}</strong><br>
                    @if(!empty($pi->seller->vendorStore->gstin))
                        GSTIN: <strong>{{ $pi->seller->vendorStore->gstin }}</strong><br>
                    @endif
                    Email: {{ $pi->seller->email }} | Phone: {{ $pi->seller->vendorStore->store_phone ?? $pi->seller->mobile }}<br>
                    Address: {{ $pi->seller->vendorStore->registered_address ?? 'Registered Seller Address' }}
                </div>
            </td>
            <td>
                <div class="box" style="margin-right: 0; margin-left: 10px;">
                    <div class="box-title">Billed To (Buyer)</div>
                    <strong>{{ $pi->buyer->businessAccount->legal_business_name ?? $pi->buyer->name }}</strong><br>
                    @if(!empty($pi->buyer->businessAccount->gstin))
                        GSTIN: <strong>{{ $pi->buyer->businessAccount->gstin }}</strong><br>
                    @endif
                    Email: {{ $pi->buyer->email }} | Phone: {{ $pi->buyer->businessAccount->business_phone ?? $pi->buyer->mobile }}<br>
                    Address: {{ $pi->buyer->businessAccount->registered_address ?? 'Buyer Delivery Address' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Unit Price (₹)</th>
                <th style="width: 15%; text-align: right;">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @if(is_array($pi->items_snapshot))
                @foreach($pi->items_snapshot as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $item['product_name'] ?? 'Product Item' }}</strong></td>
                    <td style="text-align: center;">{{ $item['quantity'] ?? 1 }}</td>
                    <td style="text-align: right;">₹{{ number_format((float)($item['unit_price'] ?? 0), 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format((float)($item['total_price'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>1</td>
                    <td><strong>Bulk Commercial Order Fulfillment</strong></td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">₹{{ number_format($pi->subtotal, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($pi->subtotal, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td style="text-align: right;">₹{{ number_format($pi->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>GST (Applicable Taxes):</td>
            <td style="text-align: right;">₹{{ number_format($pi->tax_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Shipping & Logistics:</td>
            <td style="text-align: right;">₹{{ number_format($pi->shipping_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td><strong>TOTAL AMOUNT:</strong></td>
            <td style="text-align: right;"><strong>₹{{ number_format($pi->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="bank-box">
        <strong>Bank Remittance / Payment Instructions:</strong><br>
        {{ $pi->payment_instructions ?? 'Please remit invoice total to JSS Marketplace Escrow Account via RTGS / NEFT / IMPS using invoice reference ' . $pi->proforma_number }}
    </div>

    <div class="footer">
        Generated electronically via JSS Solutions Marketplace B2B Engine | https://jsssolutions.in
    </div>
</body>
</html>
