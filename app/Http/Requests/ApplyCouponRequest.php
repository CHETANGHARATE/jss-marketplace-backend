<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Guests can test coupon codes
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'cart_total' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
