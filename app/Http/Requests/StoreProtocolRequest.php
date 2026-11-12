<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Status que o usuário pode escolher ao criar (padrão: novo). */
    public const STATUSES_INICIAIS = ['novo', 'recebido', 'encaminhado', 'encerrado', 'concluido', 'cancelado'];

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(self::STATUSES_INICIAIS)],
            'visibility' => ['nullable', 'string', 'in:public,private'],
            'assunto' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'secretaria_id' => \App\Models\Secretaria::regraId(),
            'tipo' => 'required|string|max:40|exists:protocol_types,codigo|not_in:solicitacao_cadastro',
            'prioridade' => 'nullable|string|max:20',
            'origem_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_user_id' => 'nullable|integer|exists:users,id',
            'prazo_dias' => 'nullable|integer|min:1|max:365',
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
