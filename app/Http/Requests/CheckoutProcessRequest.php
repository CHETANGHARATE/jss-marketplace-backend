<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'payment_method' => ['sometimes', 'string', 'in:cod,online,upi,wallet,card,netbanking,razorpay,stripe'],
            'shipping_method' => ['sometimes', 'nullable', 'string', 'in:standard,express,same_day'],
            'cart_items' => ['sometimes', 'nullable', 'array'],
            'cart_items.*.product_id' => ['required_with:cart_items', 'integer', 'exists:products,id'],
            'cart_items.*.quantity' => ['required_with:cart_items', 'integer', 'min:1'],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
