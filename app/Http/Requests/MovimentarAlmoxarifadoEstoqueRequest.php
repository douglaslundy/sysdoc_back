<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovimentarAlmoxarifadoEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almoxarifado_produto_id' => ['required', 'integer', 'exists:almoxarifado_produtos,id'],
            'almoxarifado_secretaria_id' => ['nullable', 'integer', 'exists:almoxarifado_secretarias,id'],
            'tipo' => ['required', 'in:entrada,saida,ajuste,transferencia'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'motivo' => ['required', 'string', 'max:150'],
            'observacao' => ['nullable', 'string'],
            'secretaria_destino_id' => ['nullable', 'integer', 'exists:almoxarifado_secretarias,id'],
        ];
    }
}
