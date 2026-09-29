====================================================
JSSSOLUTIONS MARKETPLACE — INDIA SHOPS HERE
====================================================

{{ $subject ?? 'Order Update' }}
@if(!empty($data['status_badge']))
Status: {{ $data['status_badge'] }}
@endif

----------------------------------------------------
{{ $body ?? '' }}
----------------------------------------------------

@if(!empty($data['order_number']))
ORDER DETAILS:
Order Number: #{{ $data['order_number'] }}
@if(!empty($data['order_date']))Order Date: {{ $data['order_date'] }}@endif
@if(!empty($data['payment_method']))Payment Method: {{ strtoupper($data['payment_method']) === 'COD' ? 'Cash on Delivery (COD)' : ucfirst($data['payment_method']) }}@endif
@if(!empty($data['payment_status']))Payment Status: {{ ucfirst($data['payment_status']) }}@endif
@if(!empty($data['tracking_number']) && $data['tracking_number'] !== 'N/A')Tracking Number: {{ $data['tracking_number'] }}@endif
@endif

@if(!empty($data['shipping_address_text']))
DELIVERY ADDRESS:
{{ $data['shipping_address_text'] }}
@endif

@if(!empty($data['items']) && is_array($data['items']) && count($data['items']) > 0)
ORDER ITEMS:
@foreach($data['items'] as $item)
- {{ $item['name'] ?? $item['product_name'] ?? 'Product' }} x {{ $item['quantity'] ?? 1 }} (₹{{ number_format((float)($item['unit_price'] ?? 0), 2) }} each) = ₹{{ number_format((float)($item['total_price'] ?? ($item['subtotal'] ?? 0)), 2) }}
@endforeach
@endif

@if(!empty($data['amount']) || !empty($data['total']))
FINANCIAL BREAKDOWN:
@if(!empty($data['subtotal']))Subtotal: ₹{{ number_format((float)$data['subtotal'], 2) }}@endif
@if(isset($data['tax']) && (float)$data['tax'] > 0)GST (18%): ₹{{ number_format((float)$data['tax'], 2) }}@endif
@if(isset($data['shipping']))Shipping: {{ (float)$data['shipping'] == 0 ? 'FREE' : '₹' . number_format((float)$data['shipping'], 2) }}@endif
@if(!empty($data['discount']) && (float)$data['discount'] > 0)Discount: -₹{{ number_format((float)$data['discount'], 2) }}@endif
Total Amount: ₹{{ number_format((float)($data['amount'] ?? $data['total'] ?? 0), 2) }}
@endif

----------------------------------------------------
@php
    $ctaUrl = $data['cta_url'] ?? (!empty($data['order_number']) ? 'https://jsssolutions.in/orders/' . $data['order_number'] : 'https://jsssolutions.in');
@endphp
View your order online: {{ $ctaUrl }}

----------------------------------------------------
Need help with your order?
Customer Care: +91 99966 69884 | Email: support@jsssolutions.in
Visit Help Center: https://jsssolutions.in/contact

© {{ date('Y') }} JSSSolutions Marketplace. All rights reserved.
