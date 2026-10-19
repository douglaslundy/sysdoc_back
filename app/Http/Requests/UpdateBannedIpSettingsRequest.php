<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannedIpSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // acesso restrito pelo middleware 'admin' na rota
    }

    public function rules(): array
    {
        return [
            'strike_limit' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['strike_limit' => 'limite de tentativas'];
    }
}
