<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transaction_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method_details' => $this->payment_method_details,
            'error' => $this->error_code ? [
                'code' => $this->error_code,
                'description' => $this->error_description,
            ] : null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
