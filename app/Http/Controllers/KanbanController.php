<?php

namespace App\Http\Controllers;

use App\Models\KanbanTask;
use App\Services\SystemAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KanbanTask::query()
            ->with(['protocol:id,numero,assunto,status', 'createdBy:id,name', 'updatedBy:id,name', 'responsavel:id,name'])
            ->where(function ($builder) use ($request) {
                $builder->where('visibility', 'public');

                if ($request->user()?->id) {
                    $builder->orWhere(function ($privateQuery) use ($request) {
                        $privateQuery
                            ->where('visibility', 'private')
                            ->where('created_by_id', $request->user()->id);
                    });
                }
            })
            ->orderByDesc('ordem')
            ->orderByDesc('updated_at');

        foreach (['status', 'prioridade'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($sub) use ($search) {
                $sub->where('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhereHas('protocol', function ($protocolQuery) use ($search) {
                        $protocolQuery->where('numero', 'like', "%{$search}%")
                            ->orWhere('assunto', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'status' => 'nullable|string|max:40',
            'prioridade' => 'nullable|string|max:20',
            'vencimento' => 'nullable|date',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'visibility' => 'nullable|string|in:public,private',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $task = KanbanTask::create([
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'] ?? null,
            'status' => $validated['status'] ?? 'novo',
            'prioridade' => $validated['prioridade'] ?? 'normal',
            'vencimento' => $validated['vencimento'] ?? null,
            'responsavel_id' => $validated['responsavel_id'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
            'ordem' => $validated['ordem'] ?? 0,
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]);

        $freshTask = $task->load(['protocol', 'createdBy', 'updatedBy', 'responsavel']);
        app(SystemAlertService::class)->dispatch('kanban', 'kanban_item_criado', [
            'kanban_task' => $freshTask,
            'requester' => $request->user(),
        ]);

        return response()->json($freshTask, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = KanbanTask::with(['protocol', 'createdBy', 'updatedBy', 'responsavel'])->find($id);
        if (! $task) {
            return response()->json(['message' => 'Item do kanban não encontrado.'], 404);
        }

        if ($this->isPrivateTaskFromAnotherUser($task, $request)) {
            return response()->json(['message' => 'Você não possui permissão para acessar este item.'], 403);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:200',
            'descricao' => 'nullable|string',
            'status' => 'sometimes|required|string|max:40',
            'prioridade' => 'sometimes|required|string|max:20',
            'vencimento' => 'nullable|date',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'visibility' => 'nullable|string|in:public,private',
            'ordem' => 'nullable|integer|min:0',
        ]);

        if ($task->protocol_id && array_key_exists('status', $validated) && $validated['status'] !== $task->status) {
            return response()->json([
                'message' => 'Movimente protocolos pelo fluxo de protocolo do Kanban.',
            ], 422);
        }

        $previousStatus = (string) $task->status;

        $task->update([
            ...$validated,
            'updated_by_id' => $request->user()?->id,
        ]);

        $freshTask = $task->fresh(['protocol', 'createdBy', 'updatedBy', 'responsavel']);
        app(SystemAlertService::class)->dispatch('kanban', 'kanban_item_atualizado', [
            'kanban_task' => $freshTask,
            'requester' => $request->user(),
        ]);

        if (array_key_exists('status', $validated) && (string) $validated['status'] !== $previousStatus) {
            app(SystemAlertService::class)->dispatch('kanban', 'kanban_status_alterado', [
                'kanban_task' => $freshTask,
                'requester' => $request->user(),
            ]);
        }

        return response()->json($freshTask);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $task = KanbanTask::find($id);
        if (! $task) {
            return response()->json(['message' => 'Item do kanban não encontrado.'], 404);
        }

        if ($this->isPrivateTaskFromAnotherUser($task, $request)) {
            return response()->json(['message' => 'Você não possui permissão para acessar este item.'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Item do kanban removido com sucesso.']);
    }

    private function isPrivateTaskFromAnotherUser(KanbanTask $task, Request $request): bool
    {
        return $task->visibility === 'private'
            && (int) $task->created_by_id !== (int) $request->user()?->id;
    }
}
