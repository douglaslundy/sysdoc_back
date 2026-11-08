<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKanbanRequest;
use App\Models\CadastroSolicitacao;
use App\Models\KanbanTask;
use App\Models\Protocol;
use App\Models\Scopes\CadastroProtocoloScope;
use App\Services\Protocol\UnitTree;
use App\Services\Secretarias\ActiveSecretaria;
use App\Services\Secretarias\SecretariaMembership;
use App\Services\SystemAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KanbanTask::query()
            ->with([
                'protocol:id,numero,assunto,status,tipo', 'createdBy:id,name', 'updatedBy:id,name', 'responsavel:id,name',
                'unit:id,nome,tipo', 'fiscalizacao:id,protocolo',
            ])
            ->whereNull('arquivado_at')
            ->where(function ($scope) use ($request) {
                // Cards de unidade (petições): só quem é da unidade (ou de uma subunidade) e o admin.
                $scope->whereNull('unit_id');
                $user = $request->user();
                if ($user?->profile === 'admin') {
                    $scope->orWhereNotNull('unit_id');
                } elseif ($user) {
                    $scope->orWhereIn('unit_id', UnitTree::visibleUnitIdsForUser((int) $user->id));
                }
            })
            ->where(function ($builder) use ($request) {
                $builder->where('visibility', 'public');

                if ($request->user()?->id) {
                    $builder->orWhere(function ($privateQuery) use ($request) {
                        $privateQuery
                            ->where('visibility', 'private')
                            ->where('created_by_id', $request->user()->id);
                    });
                    // Cards de pedido de cadastro: o escopo global já deixa só admin/gestor da secretaria; o subselect também.
                    $builder->orWhereIn('protocol_id', Protocol::query()->where('tipo', CadastroProtocoloScope::TIPO)->select('id'));
                }
            })
            ->orderByDesc('ordem')
            ->orderByDesc('updated_at');

        foreach (['status', 'prioridade'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->boolean('pending_only')) {
            $query->where('status', '!=', 'concluido');
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

        $tasks = $query->get();

        // Só cards de cadastro (já filtrados pelo escopo para quem pode vê-los); uma consulta para todos.
        $protocolos = $tasks->pluck('protocol')->filter(fn ($p) => $p?->tipo === CadastroProtocoloScope::TIPO);
        if ($protocolos->isNotEmpty()) {
            $ids = CadastroSolicitacao::whereIn('protocol_id', $protocolos->pluck('id'))->pluck('id', 'protocol_id');
            $protocolos->each(fn ($p) => $p->setAttribute('solicitacao_id', $ids[$p->id] ?? null));
        }

        return response()->json($tasks);
    }

    public function store(StoreKanbanRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $statusInicial = $validated['status'] ?? 'novo';

        // Admin em "Todas" informa a secretaria (o trait devolve 422 se faltar); os demais usam a ativa.
        $secretariaId = app(ActiveSecretaria::class)->forCreate($validated['secretaria_id'] ?? null);
        if (! empty($validated['responsavel_id']) && $secretariaId && ! SecretariaMembership::atende((int) $validated['responsavel_id'], $secretariaId)) {
            return response()->json(['message' => 'Usuário de outra secretaria.', 'errors' => ['responsavel_id' => ['Usuário não pertence a esta secretaria.']]], 422);
        }

        $task = KanbanTask::create([
            'secretaria_id' => $secretariaId,
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'] ?? null,
            'status' => $statusInicial,
            'prioridade' => $validated['prioridade'] ?? 'normal',
            'vencimento' => $validated['vencimento'] ?? null,
            'responsavel_id' => $validated['responsavel_id'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
            'ordem' => $validated['ordem'] ?? 0,
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
            'concluido_at' => $statusInicial === 'concluido' ? now() : null,
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

        if ($this->isPrivateTaskFromAnotherUser($task, $request) || $this->isUnitTaskFromAnotherUnit($task, $request)) {
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

        if (! empty($validated['responsavel_id']) && ! SecretariaMembership::atende((int) $validated['responsavel_id'], (int) $task->secretaria_id)) {
            return response()->json(['message' => 'Usuário de outra secretaria.', 'errors' => ['responsavel_id' => ['Usuário não pertence a esta secretaria.']]], 422);
        }

        if (! empty($validated['responsavel_id']) && $task->isCadastro()) {
            // Só quem pode abrir o pedido pode ser responsável por ele.
            $alvo = \App\Models\User::find($validated['responsavel_id']);
            if (! CadastroProtocoloScope::pode($alvo, $task->protocol?->secretaria_id)) {
                return response()->json(['message' => 'Esse usuário não pode acessar o pedido de cadastro.', 'errors' => ['responsavel_id' => ['Usuário sem acesso ao pedido.']]], 422);
            }
        }

        if ($task->protocol_id && array_key_exists('status', $validated) && $validated['status'] !== $task->status) {
            return response()->json([
                'message' => 'Movimente protocolos pelo fluxo de protocolo do Kanban.',
            ], 422);
        }

        $previousStatus = (string) $task->status;

        if (array_key_exists('status', $validated)) {
            $newStatus = (string) $validated['status'];
            if ($newStatus === 'concluido' && $previousStatus !== 'concluido') {
                $validated['concluido_at'] = now();
            } elseif ($newStatus !== 'concluido' && $previousStatus === 'concluido') {
                $validated['concluido_at'] = null;
            }
        }

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

        if ($this->isPrivateTaskFromAnotherUser($task, $request) || $this->isUnitTaskFromAnotherUnit($task, $request)) {
            return response()->json(['message' => 'Você não possui permissão para acessar este item.'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Item do kanban removido com sucesso.']);
    }

    private function isUnitTaskFromAnotherUnit(KanbanTask $task, Request $request): bool
    {
        $user = $request->user();
        if (! $task->unit_id || $user?->profile === 'admin') {
            return false;
        }

        return ! in_array((int) $task->unit_id, UnitTree::visibleUnitIdsForUser((int) $user?->id), true);
    }

    private function isPrivateTaskFromAnotherUser(KanbanTask $task, Request $request): bool
    {
        // Card de pedido de cadastro tem dono nulo: quem chegou até aqui passou pelo escopo (admin/gestor da secretaria).
        if ($task->protocol?->tipo === CadastroProtocoloScope::TIPO) {
            return false;
        }

        return $task->visibility === 'private'
            && (int) $task->created_by_id !== (int) $request->user()?->id;
    }
}
