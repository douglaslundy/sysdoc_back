<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assunto' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'tipo' => 'required|string|max:40|exists:protocol_types,codigo',
            'prioridade' => 'nullable|string|max:20',
            'origem_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_user_id' => 'nullable|integer|exists:users,id',
            'prazo_atendimento' => 'nullable|date',
            'kanban' => 'nullable|array',
            'kanban.ativar' => 'nullable|boolean',
            'kanban.id' => 'nullable|integer|exists:kanban_tasks,id',
            'kanban.titulo' => 'nullable|string|max:200',
            'kanban.descricao' => 'nullable|string',
            'kanban.status' => 'nullable|string|max:40',
            'kanban.prioridade' => 'nullable|string|max:20',
            'kanban.vencimento' => 'nullable|date',
            'kanban.responsavel_id' => 'nullable|integer|exists:users,id',
            'kanban.ordem' => 'nullable|integer|min:0',
        ];
    }
}
