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
            'short_description' => $this->short_description,
            'description' => $descVal,
            'image' => $this->thumbnail ?? $this->primaryImage?->image_url ?? ($this->relationLoaded('images') && $this->images->first() ? $this->images->first()->image_url : null),
            'images' => $this->relationLoaded('images') ? $this->images->pluck('image_url')->toArray() : [],
            'originalPrice' => (float) $this->original_price,
            'offerPrice' => (float) $this->offer_price,
            'cost_price' => (float) $this->cost_price,
            'gst_percent' => (float) $this->gst_percent,
            'tax_inclusive' => (bool) $this->tax_inclusive,
            'discountPercent' => $this->discount_percent,
            'rating' => (float) $this->rating,
            'reviewsCount' => $this->reviews_count,
            'stockStatus' => $this->stock_status,
            'stockQuantity' => $this->stock_quantity,
            'weight' => (float) $this->weight,
            'length' => (float) $this->length,
            'width' => (float) $this->width,
            'height' => (float) $this->height,
            'dispatch_days' => $this->dispatch_days,
            'shipping_charge' => (float) $this->shipping_charge,
            'is_free_shipping' => (bool) $this->is_free_shipping,
            'is_cod_available' => (bool) $this->is_cod_available,
            'return_policy' => $this->return_policy,
            'replacement_policy' => $this->replacement_policy,
            'warranty_summary' => $this->warranty_summary,
            'guarantee_summary' => $this->guarantee_summary,
            'cancellation_policy' => $this->cancellation_policy,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'og_image' => $this->og_image,
            'highlights' => $this->highlights ?? [],
            'search_keywords' => $this->search_keywords,
            'ai_description' => $this->ai_description,
            'ai_seo' => $this->ai_seo,
            'ai_highlights' => $this->ai_highlights,
            'ai_keywords' => $this->ai_keywords,
            'isFeatured' => $this->is_featured,
            'isTrending' => $this->is_trending,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
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
            'child_category' => $this->relationLoaded('childCategory') && $this->childCategory ? [
                'id' => $this->childCategory->id,
                'name' => is_array($this->childCategory->name) ? ($this->childCategory->name[$locale] ?? $this->childCategory->name['en'] ?? '') : $this->childCategory->name,
                'slug' => $this->childCategory->slug,
            ] : null,
            'seller' => $this->relationLoaded('seller') && $this->seller ? [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ] : null,
            'variants' => $this->relationLoaded('variants') ? $this->variants : [],
            'attribute_values' => $this->relationLoaded('attributeValues') ? $this->attributeValues : [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
