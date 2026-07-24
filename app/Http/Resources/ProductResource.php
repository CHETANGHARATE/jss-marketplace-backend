<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en');

        $nameVal = is_array($this->name) 
            ? ($this->name[$locale] ?? $this->name['en'] ?? reset($this->name)) 
            : $this->name;

        $descVal = is_array($this->description) 
            ? ($this->description[$locale] ?? $this->description['en'] ?? '') 
            : $this->description;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $nameVal,
            'name_translations' => $this->name,
            'slug' => $this->slug,
            'image' => $this->thumbnail ?? $this->primaryImage?->image_url,
            'originalPrice' => (float) $this->original_price,
            'offerPrice' => (float) $this->offer_price,
            'discountPercent' => $this->discount_percent,
            'rating' => (float) $this->rating,
            'reviewsCount' => $this->reviews_count,
            'stockStatus' => $this->stock_status,
            'stockQuantity' => $this->stock_quantity,
            'isFeatured' => $this->is_featured,
            'isTrending' => $this->is_trending,
            'status' => $this->status,
            'brand' => $this->relationLoaded('brand') && $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null,
            'category' => $this->relationLoaded('category') && $this->category ? [
                'id' => $this->category->id,
                'name' => is_array($this->category->name) ? ($this->category->name[$locale] ?? $this->category->name['en'] ?? '') : $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'subcategory' => $this->relationLoaded('subcategory') && $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => is_array($this->subcategory->name) ? ($this->subcategory->name[$locale] ?? $this->subcategory->name['en'] ?? '') : $this->subcategory->name,
                'slug' => $this->subcategory->slug,
            ] : null,
            'seller' => $this->relationLoaded('seller') && $this->seller ? [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
