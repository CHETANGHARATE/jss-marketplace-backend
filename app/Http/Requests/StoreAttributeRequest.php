<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:attributes,code'],
            'type' => ['required', 'string', 'in:select,text,number,checkbox,color_picker'],
            'is_filterable' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'values' => ['nullable', 'array'],
            'values.*.value' => ['required', 'string'],
            'values.*.color_code' => ['nullable', 'string', 'max:10'],
        ];
    }
}
