<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvolucaoVisitaAcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'agente_cns' => 'nullable|string|max:255',
            'desfecho' => 'nullable|integer|in:1,2,3',
            'has_geo' => 'nullable|string|in:sim,nao',
            'ano' => 'nullable|integer|min:2000|max:2099',
        ];
    }
}
