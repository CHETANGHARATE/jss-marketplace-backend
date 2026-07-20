<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSearchSynonymRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'term' => ['required', 'string', 'max:255', 'unique:search_synonyms,term'],
            'synonyms' => ['required', 'array', 'min:1'],
            'synonyms.*' => ['required', 'string', 'max:255'],
        ];
    }
}
