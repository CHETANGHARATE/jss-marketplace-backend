<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    /**
     * Transform the resource into an array with detail parameters.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $locale = $request->header('Accept-Language', 'en');

        $descVal = is_array($this->description) 
            ? ($this->description[$locale] ?? $this->description['en'] ?? '') 
            : $this->description;

        $shortDescVal = is_array($this->short_description) 
            ? ($this->short_description[$locale] ?? $this->short_description['en'] ?? '') 
            : $this->short_description;

        return array_merge($base, [
            'short_description' => $shortDescVal,
            'images' => $this->relationLoaded('images') 
                ? $this->images->map(fn($img) => $this->formatImageUrl($img->image_url))->values()->toArray() 
                : ($base['image'] ? [$base['image']] : []),
            'specifications' => ProductSpecificationResource::collection($this->whenLoaded('specifications')),
            'tags' => $this->relationLoaded('tags') ? $this->tags->pluck('tag') : [],
            'attributes' => AttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            'rejection_reason' => $this->when($request->user()?->isAdmin(), $this->rejection_reason),
            'cost_price' => $this->when($request->user()?->isAdmin() || $request->user()?->id === $this->seller_id, (float) $this->cost_price),
        ]);
    }
}
