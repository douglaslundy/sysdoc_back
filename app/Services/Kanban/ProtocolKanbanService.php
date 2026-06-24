<?php

namespace App\Services\Kanban;

use App\Models\KanbanTask;
use App\Models\Protocol;
use App\Models\User;

class ProtocolKanbanService
{
    public function sync(?Protocol $protocol, ?array $kanbanData, ?User $user = null): ?KanbanTask
    {
        if (! $protocol || ! is_array($kanbanData)) {
            return null;
        }

        $enabled = (bool) ($kanbanData['ativar'] ?? $kanbanData['enabled'] ?? false);
        $taskId = $kanbanData['id'] ?? null;

        if (! $enabled && ! $taskId) {
            return null;
        }

        $task = null;

        if ($taskId) {
            $task = KanbanTask::query()->find($taskId);
        }

        if (! $task) {
            $task = KanbanTask::query()->firstOrNew(['protocol_id' => $protocol->id]);
        }

        $task->fill([
            'protocol_id' => $protocol->id,
            'titulo' => $kanbanData['titulo'] ?? $protocol->assunto,
            'descricao' => $kanbanData['descricao'] ?? $protocol->descricao,
            'status' => $kanbanData['status'] ?? 'novo',
            'prioridade' => $kanbanData['prioridade'] ?? $protocol->prioridade ?? 'normal',
            'vencimento' => $kanbanData['vencimento'] ?? $protocol->prazo_atendimento,
            'responsavel_id' => $kanbanData['responsavel_id'] ?? $protocol->responsavel_atual_id,
            'updated_by_id' => $user?->id,
            'created_by_id' => $task->exists ? $task->created_by_id : $user?->id,
        ]);

        $task->save();

        return $task->fresh(['protocol', 'createdBy', 'updatedBy', 'responsavel']);
    }
}
