<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVigilanciaConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => 'nullable|string|max:2',
            'nome_municipio' => 'nullable|string|max:255',
            'nome_prefeitura' => 'nullable|string|max:255',
            'cnpj_prefeitura' => 'nullable|string|max:14',
            'nome_secretaria' => 'nullable|string|max:255',
            'cnpj_secretaria' => 'nullable|string|max:14',
            'divisao' => 'nullable|string|max:255',
            'endereco' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:8',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nome_responsavel' => 'nullable|string|max:255',
            'cargo_responsavel' => 'nullable|string|max:255',
            'grant_type' => 'nullable|string|max:255',
            'observacoes' => 'nullable|array',
            'observacoes.*' => 'string|max:500',
        ];
    }
}
