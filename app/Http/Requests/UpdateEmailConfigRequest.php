<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|max:20',
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'email_ativo' => 'boolean',
        ];
    }
}
