<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCidadaoAcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ine'              => 'nullable|string',
            'profissional_id'  => 'nullable|integer',
            'agente'           => 'nullable|string|max:255',
            'agente_cns'       => 'nullable|string|max:255',
            'condicao'         => 'nullable|string|in:gestante,has,dm,idoso,obito',
            'busca'            => 'nullable|string|min:3|max:100',
            'multi_domicilio'  => 'nullable|boolean',
            'sort'             => 'nullable|string|in:nome,idade',
            'dir'              => 'nullable|string|in:asc,desc',
            'page'             => 'nullable|integer|min:1',
            'per_page'         => 'nullable|integer|min:10|max:200',
        ];
    }
}
