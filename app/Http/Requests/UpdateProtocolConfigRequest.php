<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProtocolConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allow_external_protocols' => 'boolean',
            'allow_reopen' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'default_priority' => 'nullable|string|max:20',
            'default_due_days' => 'nullable|integer|min:1|max:365',
            'observacoes' => 'nullable|string',
        ];
    }
}
