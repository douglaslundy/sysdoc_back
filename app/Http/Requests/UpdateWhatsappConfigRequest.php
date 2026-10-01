<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsappConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'whatsapp_base_url' => 'nullable|string|max:255',
            'whatsapp_api_key' => 'nullable|string',
            'whatsapp_instance_name' => 'nullable|string|max:120',
            'whatsapp_instance_token' => 'nullable|string',
            'whatsapp_ativo' => 'boolean',
        ];
    }
}
