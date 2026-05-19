<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstabelecimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_responsavel' => ['required', 'string', 'max:255'],
            'nome_estabelecimento' => ['required', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18', 'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['required', 'string', 'max:500'],
            'cnaes' => ['required', 'string', 'max:1000'],
            'obs' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_responsavel.required' => 'O nome do responsável é obrigatório.',
            'nome_estabelecimento.required' => 'O nome do estabelecimento é obrigatório.',
            'endereco.required' => 'O endereço é obrigatório.',
            'cnaes.required' => 'Os CNAEs são obrigatórios.',
        ];
    }
}
