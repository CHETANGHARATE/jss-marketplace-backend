<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'status' => $this->status === 'processing' ? 'packed' : $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'shipping_address' => $this->shipping_address_snapshot,
            'billing_address' => $this->billing_address_snapshot,
            'shipping_address_snapshot' => $this->shipping_address_snapshot,
            'billing_address_snapshot' => $this->billing_address_snapshot,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_fee' => (float) $this->shipping_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'discount_amount' => (float) $this->discount_amount,
            'loyalty_points_redeemed' => (int) ($this->loyalty_points_redeemed ?? 0),
            'loyalty_discount_amount' => (float) ($this->loyalty_discount_amount ?? 0.00),
            'total_amount' => (float) $this->total_amount,
            'tracking_number' => $this->tracking_number ?? null,
            'courier_name' => $this->courier_name ?? null,
            'tracking_url' => $this->tracking_url ?? ($this->tracking_number ? "https://jsssolutions.in/track/{$this->tracking_number}" : null),
            'financials' => [
                'subtotal' => (float) $this->subtotal,
                'tax' => (float) $this->tax_amount,
                'shipping' => (float) $this->shipping_amount,
                'discount' => (float) $this->discount_amount,
                'loyalty_points_redeemed' => (int) ($this->loyalty_points_redeemed ?? 0),
                'loyalty_discount' => (float) ($this->loyalty_discount_amount ?? 0.00),
                'total' => (float) $this->total_amount,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'cancellation' => $this->status === 'cancelled' ? [
                'reason' => $this->cancellation_reason,
                'at' => $this->cancelled_at instanceof \Carbon\CarbonInterface ? $this->cancelled_at->toIso8601String() : (is_string($this->cancelled_at) ? $this->cancelled_at : null),
            ] : null,
            'created_at' => $this->created_at instanceof \Carbon\CarbonInterface ? $this->created_at->toIso8601String() : (is_string($this->created_at) ? $this->created_at : null),
        ];
    }
}
