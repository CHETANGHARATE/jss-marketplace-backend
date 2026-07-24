<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,mp4,pdf', 'max:10240'], // 10MB limit
            'model_type' => ['nullable', 'string'],
            'model_id' => ['nullable', 'integer'],
            'collection' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
