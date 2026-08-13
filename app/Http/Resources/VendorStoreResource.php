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
            'user_id' => $this->user_id,
            'owner_name' => $this->user?->name ?? 'N/A',
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'store_name' => $this->store_name,
            'slug' => $this->slug,
            'store_email' => $this->store_email ?? $this->user?->email,
            'store_phone' => $this->store_phone ?? $this->user?->phone,
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
            'kyc_documents' => $this->kyc_documents,
            'status' => $this->status,
            'commission_rate' => (float) $this->commission_rate,
            'wallet' => new VendorWalletResource($this->whenLoaded('wallet')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
