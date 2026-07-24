<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'status' => $this->status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'rejection_reason' => $this->when(auth()->check() && auth()->user()->isAdmin(), $this->rejection_reason),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
