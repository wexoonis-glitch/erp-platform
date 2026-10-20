<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'     => ['sometimes', 'string', 'max:255'],
            'config'    => ['sometimes', 'array'],
            'entity'    => ['prohibited'],
            'code'      => ['prohibited'],
            'data_type' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity.prohibited'    => 'Bu alan güncellenemez: entity',
            'code.prohibited'      => 'Bu alan güncellenemez: code',
            'data_type.prohibited' => 'Bu alan güncellenemez: data_type',
        ];
    }
}
