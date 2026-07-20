<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'items_count' => $this->items_count,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
