<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1.00'],
            'bank_details' => ['required', 'array'],
            'bank_details.account_number' => ['required', 'string'],
            'bank_details.ifsc_code' => ['required', 'string'],
            'bank_details.bank_name' => ['required', 'string'],
            'bank_details.account_holder' => ['required', 'string'],
        ];
    }
}
