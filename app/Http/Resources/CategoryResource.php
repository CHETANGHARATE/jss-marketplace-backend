<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'name' => $nameVal,
            'name_translations' => $this->name,
            'slug' => $this->slug,
            'description' => $descVal,
            'icon' => $this->icon,
            'image' => $this->image,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            'subcategories' => CategoryResource::collection($this->whenLoaded('children')),
            'popularBrands' => BrandResource::collection($this->whenLoaded('brands')),
            'attributes' => AttributeResource::collection($this->whenLoaded('attributes')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
