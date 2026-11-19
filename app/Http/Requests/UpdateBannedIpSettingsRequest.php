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
            'strike_limit' => ['required_without:ban_days', 'integer', 'min:1', 'max:1000'],
            'ban_days' => ['required_without:strike_limit', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function attributes(): array
    {
        return ['strike_limit' => 'limite de tentativas', 'ban_days' => 'dias de banimento'];
    }
}
