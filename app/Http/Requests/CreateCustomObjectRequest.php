<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'   => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]{0,49}$/'],
            'label'  => ['required', 'string', 'max:255'],
            'schema' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kod küçük harfle başlamalı; sadece küçük harf, rakam ve alt çizgi.',
        ];
    }
}
