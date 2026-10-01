<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveProtocolKanbanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kanban_status' => 'required|in:novo,em_andamento,aguardando_resposta,bloqueado,concluido',
            'observacao' => 'nullable|string|max:1000',
        ];
    }
}
