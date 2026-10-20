<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'tier'   => ['sometimes', 'in:shared,schema,dedicated'],
            'config' => ['sometimes', 'array'],
        ];
    }
}
