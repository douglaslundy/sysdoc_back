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
            'nome_responsavel'     => ['sometimes', 'required', 'string', 'max:255'],
            'nome_estabelecimento' => ['sometimes', 'required', 'string', 'max:255'],
            'endereco'             => ['sometimes', 'required', 'string', 'max:500'],
            'cnaes'                => ['sometimes', 'required', 'string', 'max:1000'],
        ];
    }
}
