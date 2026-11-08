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

        // Participante de outra secretaria não cria nem mexe no card da secretaria de origem.
        $ativa = app(\App\Services\Secretarias\ActiveSecretaria::class);
        if ($protocol->secretaria_id && ! $ativa->isAll() && $ativa->inRequest() && (int) $protocol->secretaria_id !== (int) $ativa->id()) {
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
            // Admin em "Todas" enxerga cards de qualquer secretaria: nunca reaproveita o de outra.
            if ($task && (int) $task->secretaria_id !== (int) $protocol->secretaria_id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['kanban.id' => 'Card de outra secretaria.']);
            }
        }

        if (! $task) {
            $task = KanbanTask::query()->firstOrNew(['protocol_id' => $protocol->id]);
        }

        $novoStatus = $kanbanData['status'] ?? 'novo';
        $statusAnterior = $task->exists ? (string) $task->status : null;

        $concluido_at = $task->concluido_at;
        if ($novoStatus === 'concluido' && $statusAnterior !== 'concluido') {
            $concluido_at = now();
        } elseif ($novoStatus !== 'concluido') {
            $concluido_at = null;
        }

        $task->fill([
            'protocol_id' => $protocol->id,
            'secretaria_id' => $protocol->secretaria_id, // o card é sempre da secretaria do protocolo
            'titulo' => $kanbanData['titulo'] ?? $protocol->assunto,
            'descricao' => $kanbanData['descricao'] ?? $protocol->descricao,
            'status' => $novoStatus,
            'prioridade' => $kanbanData['prioridade'] ?? $protocol->prioridade ?? 'normal',
            'vencimento' => $kanbanData['vencimento'] ?? $protocol->prazo_atendimento,
            'responsavel_id' => $kanbanData['responsavel_id'] ?? $protocol->responsavel_atual_id,
            'visibility' => 'public',
            'updated_by_id' => $user?->id,
            'created_by_id' => $task->exists ? $task->created_by_id : $user?->id,
            'concluido_at' => $concluido_at,
        ]);

        $task->save();

        return $task->fresh(['protocol', 'createdBy', 'updatedBy', 'responsavel']);
    }
}
