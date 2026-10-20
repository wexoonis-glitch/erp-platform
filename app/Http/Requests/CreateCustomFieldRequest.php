<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity'    => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]{0,49}$/'],
            'label'     => ['required', 'string', 'max:255'],
            'data_type' => ['required', 'in:string,number,date,bool,enum,json'],
            'config'    => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kod küçük harfle başlamalı; sadece küçük harf, rakam ve alt çizgi içerebilir.',
        ];
    }
}
