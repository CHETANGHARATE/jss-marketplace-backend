<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSeller());
    }

    public function rules(): array
    {
        return [
            'answer' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
