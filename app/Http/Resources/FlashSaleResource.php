<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'discount_percentage' => (float) $this->discount_percentage,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'products' => $this->products->map(fn ($p) => [
                'product_id' => $p->product_id,
                'flash_price' => (float) $p->flash_price,
                'quantity_limit' => $p->quantity_limit,
                'sold_quantity' => $p->sold_quantity,
                'product' => new ProductResource($p->product),
            ]),
        ];
    }
}
