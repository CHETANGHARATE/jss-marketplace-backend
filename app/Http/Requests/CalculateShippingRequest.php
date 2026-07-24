<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'pincode' => ['required', 'string', 'max:10'],
            'cart_total' => ['sometimes', 'numeric', 'min:0'],
            'weight_kg' => ['sometimes', 'numeric', 'min:0.01'],
        ];
    }
}
