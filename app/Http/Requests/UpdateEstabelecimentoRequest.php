<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstabelecimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_responsavel' => ['sometimes', 'required', 'string', 'max:255'],
            'nome_estabelecimento' => ['sometimes', 'required', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18', 'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['sometimes', 'required', 'string', 'max:500'],
            'cnaes' => ['sometimes', 'required', 'string', 'max:1000'],
            'obs' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
