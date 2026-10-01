<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlmoxarifadoRequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almoxarifado_secretaria_id' => ['required', 'integer', 'exists:almoxarifado_secretarias,id'],
            'justificativa' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.almoxarifado_produto_id' => ['required', 'integer', 'exists:almoxarifado_produtos,id'],
            'itens.*.quantidade_solicitada' => ['required', 'numeric', 'min:0.001'],
            'itens.*.observacao' => ['nullable', 'string'],
        ];
    }
}
