<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refund_number' => $this->refund_number,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'gateway_refund_id' => $this->gateway_refund_id,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
