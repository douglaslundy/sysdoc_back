<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'          => 'required|exists:clients,id',
            'medico_solicitante_id' => 'nullable|exists:medicos_solicitantes,id',
            'data_pedido'        => 'required|date',
            'data_coleta'        => 'nullable|date',
            'observacoes'        => 'nullable|string',
            'exames'             => 'required|array|min:1',
            'exames.*'           => 'required|integer|distinct|exists:exames,id',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O campo cliente é obrigatório.',
            'client_id.exists'   => 'Cliente não encontrado.',
            'data_pedido.required' => 'A data do pedido é obrigatória.',
            'exames.required'    => 'Selecione ao menos um exame.',
            'exames.min'         => 'Selecione ao menos um exame.',
            'exames.*.exists'    => 'Um dos exames selecionados não existe.',
            'exames.*.distinct'  => 'O mesmo exame não pode ser selecionado mais de uma vez.',
        ];
    }
}
