<?php

namespace App\Http\Requests;

use App\Http\Controllers\KanbanController;
use App\Models\KanbanTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateKanbanTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 404 e 403 do item vêm antes da validação, como no controller original.
        $task = KanbanTask::with('protocol')->find($this->route('id'));
        if (! $task) {
            throw new HttpResponseException(response()->json(['message' => 'Item do kanban não encontrado.'], 404));
        }

        $controller = app(KanbanController::class);
        if ($controller->isPrivateTaskFromAnotherUser($task, $this) || $controller->isUnitTaskFromAnotherUnit($task, $this)) {
            throw new HttpResponseException(response()->json(['message' => 'Você não possui permissão para acessar este item.'], 403));
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'sometimes|required|string|max:200',
            'descricao' => 'nullable|string',
            'status' => 'sometimes|required|string|max:40',
            'prioridade' => 'sometimes|required|string|max:20',
            'vencimento' => 'nullable|date',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'visibility' => 'nullable|string|in:public,private',
            'ordem' => 'nullable|integer|min:0',
        ];
    }
}
