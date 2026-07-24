<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_number' => $this->shipment_number,
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'weight_kg' => (float) $this->weight_kg,
            'shipping_cost' => (float) $this->shipping_cost,
            'courier' => new CourierResource($this->whenLoaded('courier')),
            'logs' => ShipmentLogResource::collection($this->whenLoaded('logs')),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
