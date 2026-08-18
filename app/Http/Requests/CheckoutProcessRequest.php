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
            'payment_method' => ['sometimes', 'string', 'in:cod,online,upi,wallet,card,netbanking'],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
