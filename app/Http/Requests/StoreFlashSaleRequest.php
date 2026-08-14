<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlashSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        // Support both 'title' and 'name'
        if (!$this->has('title') && $this->has('name')) {
            $this->merge(['title' => $this->input('name')]);
        }
        if (!$this->has('discount_percentage') && $this->has('discount')) {
            $this->merge(['discount_percentage' => $this->input('discount')]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'discount_percentage' => ['required', 'numeric', 'between:1,99'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.quantity_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
