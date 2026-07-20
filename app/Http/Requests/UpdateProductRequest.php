<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('id');

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'array'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'short_description' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'original_price' => ['sometimes', 'numeric', 'min:0'],
            'offer_price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_status' => ['sometimes', 'string', 'in:in_stock,out_of_stock,pre_order'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_trending' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:draft,pending_approval,approved,rejected,archived'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:500'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.key' => ['required', 'string'],
            'specifications.*.value' => ['required', 'string'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
        ];
    }
}
