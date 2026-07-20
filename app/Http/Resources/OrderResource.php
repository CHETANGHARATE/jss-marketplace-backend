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
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'shipping_address' => $this->shipping_address_snapshot,
            'billing_address' => $this->billing_address_snapshot,
            'financials' => [
                'subtotal' => (float) $this->subtotal,
                'tax' => (float) $this->tax_amount,
                'shipping' => (float) $this->shipping_amount,
                'discount' => (float) $this->discount_amount,
                'total' => (float) $this->total_amount,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'cancellation' => $this->status === 'cancelled' ? [
                'reason' => $this->cancellation_reason,
                'at' => $this->cancelled_at?->toIso8601String(),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
