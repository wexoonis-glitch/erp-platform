<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'  => ['sometimes', 'string', 'max:255'],
            'schema' => ['sometimes', 'array'],
            'code'   => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.prohibited' => 'Bu alan güncellenemez: code',
        ];
    }
}
