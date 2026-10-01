<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapaVisitaAcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'agente_cns' => 'nullable|string|max:255',
            'busca' => 'nullable|string|max:200',
        ];
    }
}
