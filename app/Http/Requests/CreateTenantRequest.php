<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Yetki kontrolü servis içinde (superadmin) yapılıyor
        return true;
    }

    public function rules(): array
    {
        return [
            'code'   => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/'],
            'name'   => ['required', 'string', 'max:255'],
            'tier'   => ['nullable', 'in:shared,schema,dedicated'],
            'config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kod sadece küçük harf, rakam ve tire içerebilir; tire ile başlayıp bitemez.',
        ];
    }
}
