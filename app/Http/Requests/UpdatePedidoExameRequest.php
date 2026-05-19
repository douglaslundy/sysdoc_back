<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medico_solicitante_id' => 'nullable|exists:medicos_solicitantes,id',
            'data_pedido' => 'nullable|date',
            'data_coleta' => 'nullable|date',
            'observacoes' => 'nullable|string|max:1000',
            'exames' => 'nullable|array|min:1',
            'exames.*' => 'integer|distinct|exists:exames,id',
        ];
    }

    public function messages(): array
    {
        return [
            'medico_solicitante_id.exists' => 'Médico solicitante não encontrado.',
            'data_pedido.date' => 'A data do pedido deve ser uma data válida.',
            'data_coleta.date' => 'A data de coleta deve ser uma data válida.',
            'observacoes.max' => 'As observações não podem ultrapassar 1000 caracteres.',
            'exames.min' => 'Selecione ao menos um exame.',
            'exames.*.integer' => 'O identificador do exame deve ser um número inteiro.',
            'exames.*.distinct' => 'O mesmo exame não pode ser selecionado mais de uma vez.',
            'exames.*.exists' => 'Um dos exames selecionados não existe.',
        ];
    }
}
