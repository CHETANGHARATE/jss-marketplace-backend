<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => (float) $this->balance,
            'pending_balance' => (float) $this->pending_balance,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'transactions' => VendorTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
