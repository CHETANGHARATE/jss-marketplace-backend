<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'countries' => $this->countries,
            'states' => $this->states,
            'pincodes' => $this->pincodes,
            'is_active' => $this->is_active,
        ];
    }
}
