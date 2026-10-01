<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMonitorApsConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'host'           => 'required|string',
            'database'       => 'required|string',
            'user'           => 'required|string',
            'port'           => 'nullable|integer',
            'password'       => 'nullable|string',
            'municipio_ibge' => 'nullable|string',
            'municipio_nome' => 'nullable|string',
            'estrato_ied'    => 'nullable|integer|min:1|max:4',
        ];
    }
}
