<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:order_issue,payment,shipping,product_inquiry,general'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }
}
