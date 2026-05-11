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
            'nome_responsavel'     => ['required', 'string', 'max:255'],
            'nome_estabelecimento' => ['required', 'string', 'max:255'],
            'endereco'             => ['required', 'string', 'max:500'],
            'cnaes'                => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_responsavel.required'     => 'O nome do responsável é obrigatório.',
            'nome_estabelecimento.required' => 'O nome do estabelecimento é obrigatório.',
            'endereco.required'             => 'O endereço é obrigatório.',
            'cnaes.required'                => 'Os CNAEs são obrigatórios.',
        ];
    }
}
