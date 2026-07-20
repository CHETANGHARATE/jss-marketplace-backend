<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorStoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'slug' => $this->slug,
            'store_email' => $this->store_email,
            'store_phone' => $this->store_phone,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'description' => $this->description,
            'address' => [
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
            ],
            'kyc_status' => $this->kyc_status,
            'status' => $this->status,
            'commission_rate' => (float) $this->commission_rate,
            'wallet' => new VendorWalletResource($this->whenLoaded('wallet')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
