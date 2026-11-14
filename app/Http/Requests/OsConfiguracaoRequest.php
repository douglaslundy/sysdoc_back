<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OsConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile === 'admin';
    }

    public function rules(): array
    {
        return [
            // Nulo = padrão global.
            'secretaria_id' => ['nullable', 'integer', 'exists:secretarias,id'],
            'solicitante_aponta_tecnico' => ['required', 'boolean'],
        ];
    }
}
