<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Protocol;
use App\Models\ProtocolAttachment;
use App\Models\ProtocolComment;
use App\Models\ProtocolConfig;
use App\Models\ProtocolMovement;
use App\Models\ProtocolNotification;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolView;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProtocolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request->user());

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('assunto', 'like', "%{$search}%")
                    ->orWhere('solicitante_nome', 'like', "%{$search}%")
                    ->orWhere('solicitante_documento', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'prioridade', 'tipo'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('responsavel_atual_id')) {
            $query->where('responsavel_atual_id', $request->integer('responsavel_atual_id'));
        }

        if ($request->filled('origem_unit_id')) {
            $query->where('origem_unit_id', $request->integer('origem_unit_id'));
        }

        if ($request->filled('destino_unit_id')) {
            $query->where('destino_unit_id', $request->integer('destino_unit_id'));
        }

        if ($request->filled('vencimento')) {
            $query->whereDate('prazo_atendimento', $request->date('vencimento'));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        return response()->json($query->paginate($perPage));
    }

    public function inbox(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request->user())->where(function ($q) use ($request) {
            $q->where('responsavel_atual_id', $request->user()?->id);

            $unitIds = $this->visibleUnitIds($request->user());
            if ($unitIds) {
                $q->orWhereIn('destino_unit_id', $unitIds)
                    ->orWhereIn('origem_unit_id', $unitIds);
            }
        });

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->input('prioridade'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('assunto', 'like', "%{$search}%")
                    ->orWhere('solicitante_nome', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderByDesc('novo')->orderByDesc('updated_at')->paginate(max(1, min(100, (int) $request->input('per_page', 15)))));
    }

    public function show(int $id): JsonResponse
    {
        $protocol = Protocol::with([
            'origemUnit:id,nome,tipo',
            'destinoUnit:id,nome,tipo',
            'responsavelAtual:id,name',
            'criadoPor:id,name',
            'movements.user:id,name',
            'comments.user:id,name',
            'attachments.user:id,name',
            'notifications.user:id,name',
            'visualizations.user.equipeAps',
        ])->find($id);

        if (! $protocol || ! $this->canAccess($protocol, request()->user())) {
            return response()->json(['message' => 'Protocolo nÃ£o encontrado.'], 404);
        }

        if ($protocol->novo) {
            $protocol->update(['novo' => false]);
        }

        $this->recordVisualization($protocol, request()->user());

        return response()->json($protocol->fresh([
            'origemUnit:id,nome,tipo',
            'destinoUnit:id,nome,tipo',
            'responsavelAtual:id,name',
            'criadoPor:id,name',
            'movements.user:id,name',
            'comments.user:id,name',
            'attachments.user:id,name',
            'notifications.user:id,name',
            'visualizations.user.equipeAps',
        ]));
    }

    public function visualizations(int $id): JsonResponse
    {
        $protocol = Protocol::find($id);
        if (! $protocol || ! $this->canAccess($protocol, request()->user())) {
            return response()->json(['message' => 'Protocolo não encontrado.'], 404);
        }

        $views = ProtocolView::query()
            ->with(['user.equipeAps'])
            ->where('protocol_id', $protocol->id)
            ->orderByDesc('visualized_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProtocolView $view) {
                $user = $view->user;
                $equipe = optional($user?->equipeAps->first())->no_equipe;

                return [
                    'id' => $view->id,
                    'protocol_id' => $view->protocol_id,
                    'user' => [
                        'id' => $user?->id,
                        'name' => $user?->name,
                    ],
                    'departamento' => $view->departamento ?: ($equipe ?: 'Sem equipe informada'),
                    'equipe' => $view->equipe ?: $equipe,
                    'visualized_at' => $view->visualized_at ?? $view->created_at,
                ];
            });

        return response()->json($views);
    }

    public function historico(int $id): JsonResponse
    {
        $protocol = Protocol::with(['movements.user:id,name'])->find($id);
        if (! $protocol || ! $this->canAccess($protocol, request()->user())) {
            return response()->json(['message' => 'Protocolo não encontrado.'], 404);
        }

        $historico = $protocol->movements
            ->sortByDesc(fn (ProtocolMovement $movement) => $movement->created_at?->getTimestamp() ?? 0)
            ->values()
            ->map(function (ProtocolMovement $movement) {
                return [
                    'id' => $movement->id,
                    'acao' => $movement->acao,
                    'status_anterior' => $movement->status_anterior,
                    'status_novo' => $movement->status_novo,
                    'observacao' => $movement->observacao,
                    'dados' => $movement->dados,
                    'user' => [
                        'id' => $movement->user?->id,
                        'name' => $movement->user?->name,
                    ],
                    'created_at' => $movement->created_at,
                ];
            });

        return response()->json($historico);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assunto' => 'required|string|max:200',
            'descricao' => 'nullable|string',
            'tipo' => 'required|string|max:40',
            'prioridade' => 'nullable|string|max:20',
            'solicitante_tipo' => 'required|string|max:20',
            'solicitante_nome' => 'nullable|string|max:150',
            'solicitante_documento' => 'nullable|string|max:40',
            'origem_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'prazo_atendimento' => 'nullable|date',
        ]);

        $config = ProtocolConfig::current();

        $protocol = DB::transaction(function () use ($request, $validated, $config) {
            $protocol = Protocol::create([
                'numero' => Protocol::gerarNumero(),
                'assunto' => $validated['assunto'],
                'descricao' => $validated['descricao'] ?? null,
                'tipo' => $validated['tipo'],
                'status' => 'novo',
                'prioridade' => $validated['prioridade'] ?? $config->default_priority ?? 'normal',
                'solicitante_tipo' => $validated['solicitante_tipo'],
                'solicitante_nome' => $validated['solicitante_nome'] ?? null,
                'solicitante_documento' => $validated['solicitante_documento'] ?? null,
                'origem_unit_id' => $validated['origem_unit_id'] ?? null,
                'destino_unit_id' => $validated['destino_unit_id'] ?? null,
                'criado_por_id' => $request->user()?->id,
                'prazo_atendimento' => $validated['prazo_atendimento'] ?? now()->addDays((int) $config->default_due_days)->toDateString(),
                'novo' => true,
                'vencido' => false,
            ]);

            $this->movimentar($protocol, 'criado', null, 'novo', $request->user()?->id, [
                'assunto' => $protocol->assunto,
                'tipo' => $protocol->tipo,
            ]);

            AuditService::record('CREATE', $protocol, null, $protocol->toArray());

            return $protocol->load(['origemUnit', 'destinoUnit', 'responsavelAtual', 'criadoPor']);
        });

        return response()->json($protocol, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $protocol = Protocol::find($id);
        if (! $protocol || ! $this->canAccess($protocol, $request->user())) {
            return response()->json(['message' => 'Protocolo nÃ£o encontrado.'], 404);
        }

        $validated = $request->validate([
            'assunto' => 'sometimes|required|string|max:200',
            'descricao' => 'nullable|string',
            'tipo' => 'sometimes|required|string|max:40',
            'prioridade' => 'nullable|string|max:20',
            'solicitante_tipo' => 'sometimes|required|string|max:20',
            'solicitante_nome' => 'nullable|string|max:150',
            'solicitante_documento' => 'nullable|string|max:40',
            'origem_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'prazo_atendimento' => 'nullable|date',
        ]);

        $old = $protocol->toArray();
        $protocol->update($validated);
        AuditService::record('UPDATE', $protocol, $old, $protocol->fresh()->toArray());

        return response()->json($protocol->fresh());
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        return $this->applyAction($request, $id, 'recebido', function (Protocol $protocol) use ($request) {
            $protocol->update([
                'status' => 'recebido',
                'recebido_em' => now(),
                'responsavel_atual_id' => $request->user()?->id,
                'novo' => false,
            ]);
        });
    }

    public function forward(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'destino_unit_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'destino_user_id' => 'nullable|integer|exists:users,id',
            'observacao' => 'nullable|string',
        ]);

        return $this->applyAction($request, $id, 'encaminhado', function (Protocol $protocol) use ($request, $validated) {
            $protocol->update([
                'status' => 'encaminhado',
                'destino_unit_id' => $validated['destino_unit_id'] ?? $protocol->destino_unit_id,
                'responsavel_atual_id' => $validated['destino_user_id'] ?? null,
                'encaminhado_em' => now(),
                'novo' => false,
            ]);
            $this->movimentar($protocol, 'encaminhado', $protocol->origem_unit_id, 'encaminhado', $request->user()?->id, $validated);
        });
    }

    public function comment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'conteudo' => 'required|string',
            'privado' => 'nullable|boolean',
            'tipo' => 'nullable|string|max:30',
        ]);

        return $this->applyAction($request, $id, 'comentado', function (Protocol $protocol) use ($request, $validated) {
            ProtocolComment::create([
                'protocol_id' => $protocol->id,
                'user_id' => $request->user()?->id,
                'tipo' => $validated['tipo'] ?? 'comentario',
                'conteudo' => $validated['conteudo'],
                'privado' => $validated['privado'] ?? false,
            ]);

            $this->movimentar($protocol, 'comentario', null, $protocol->status, $request->user()?->id, $validated);
        });
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'justificativa_encerramento' => 'required|string',
        ]);

        return $this->applyAction($request, $id, 'encerrado', function (Protocol $protocol) use ($request, $validated) {
            $protocol->update([
                'status' => 'encerrado',
                'encerrado_em' => now(),
                'justificativa_encerramento' => $validated['justificativa_encerramento'],
                'novo' => false,
            ]);
            $this->movimentar($protocol, 'encerrado', null, 'encerrado', $request->user()?->id, $validated);
        });
    }

    public function reopen(Request $request, int $id): JsonResponse
    {
        $config = ProtocolConfig::current();
        if (! $config->allow_reopen) {
            return response()->json(['message' => 'Reabertura desativada nas configuraÃ§Ãµes.'], 422);
        }

        return $this->applyAction($request, $id, 'reaberto', function (Protocol $protocol) use ($request) {
            $protocol->update([
                'status' => 'reaberto',
                'reaberto_em' => now(),
                'novo' => true,
            ]);
            $this->movimentar($protocol, 'reaberto', null, 'reaberto', $request->user()?->id);
        });
    }

    public function attach(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'arquivo' => 'required|file|max:20480',
            'descricao' => 'nullable|string|max:255',
        ]);

        $protocol = Protocol::find($id);
        if (! $protocol || ! $this->canAccess($protocol, $request->user())) {
            return response()->json(['message' => 'Protocolo nÃ£o encontrado.'], 404);
        }

        $file = $validated['arquivo'];
        $path = $file->store('protocolos', 'public');

        $attachment = ProtocolAttachment::create([
            'protocol_id' => $protocol->id,
            'user_id' => $request->user()?->id,
            'nome_original' => $file->getClientOriginalName(),
            'caminho' => $path,
            'mime_type' => $file->getMimeType(),
            'tamanho_bytes' => $file->getSize(),
            'descricao' => $validated['descricao'] ?? null,
            'ativo' => true,
        ]);

        $this->movimentar($protocol, 'anexo', null, $protocol->status, $request->user()?->id, ['attachment_id' => $attachment->id]);
        AuditService::record('ATTACH', $protocol, null, $attachment->toArray());

        return response()->json($attachment, 201);
    }

    public function downloadAttachment(Request $request, int $attachment): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $attachmentModel = ProtocolAttachment::with('protocol')->find($attachment);
        if (! $attachmentModel || ! $attachmentModel->protocol || ! $this->canAccess($attachmentModel->protocol, $request->user())) {
            return response()->json(['message' => 'Anexo nÃ£o encontrado.'], 404);
        }

        if (! Storage::disk('public')->exists($attachmentModel->caminho)) {
            return response()->json(['message' => 'Arquivo do anexo nÃ£o encontrado.'], 404);
        }

        return Storage::disk('public')->download($attachmentModel->caminho, $attachmentModel->nome_original);
    }

    public function counts(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request->user())->where('novo', true);
        return response()->json([
            'novos' => $query->count(),
            'vence_em_breve' => (clone $query)->whereDate('prazo_atendimento', '<=', now()->addDays(3))->count(),
            'vencidos' => (clone $query)->whereDate('prazo_atendimento', '<', now())->count(),
            'recentes' => (clone $query)
                ->with(['origemUnit:id,nome,tipo', 'destinoUnit:id,nome,tipo', 'responsavelAtual:id,name'])
                ->limit(8)
                ->get(['id', 'numero', 'assunto', 'status', 'prioridade', 'prazo_atendimento', 'origem_unit_id', 'destino_unit_id', 'responsavel_atual_id', 'updated_at']),
        ]);
    }

    private function baseQuery(?User $user)
    {
        return Protocol::query()
            ->with(['origemUnit:id,nome,tipo', 'destinoUnit:id,nome,tipo', 'responsavelAtual:id,name', 'criadoPor:id,name'])
            ->when(! $this->isAdmin($user), function ($q) use ($user) {
                $unitIds = $this->visibleUnitIds($user);
                $q->where(function ($sub) use ($user, $unitIds) {
                    $sub->where('responsavel_atual_id', $user?->id)
                        ->orWhere('criado_por_id', $user?->id);
                    if ($unitIds) {
                        $sub->orWhereIn('origem_unit_id', $unitIds)
                            ->orWhereIn('destino_unit_id', $unitIds);
                    }
                });
            })
            ->orderByDesc('novo')
            ->orderByDesc('updated_at');
    }

    private function visibleUnitIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return ProtocolUserUnit::query()
            ->where('user_id', $user->id)
            ->where('ativo', true)
            ->pluck('protocol_organizational_unit_id')
            ->all();
    }

    private function isAdmin(?User $user): bool
    {
        return (string) ($user?->profile ?? '') === 'admin';
    }

    private function canAccess(Protocol $protocol, ?User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $unitIds = $this->visibleUnitIds($user);
        return $protocol->responsavel_atual_id === $user?->id
            || $protocol->criado_por_id === $user?->id
            || in_array($protocol->origem_unit_id, $unitIds, true)
            || in_array($protocol->destino_unit_id, $unitIds, true);
    }

    private function applyAction(Request $request, int $id, string $acao, \Closure $callback): JsonResponse
    {
        $protocol = Protocol::find($id);
        if (! $protocol || ! $this->canAccess($protocol, $request->user())) {
            return response()->json(['message' => 'Protocolo nÃ£o encontrado.'], 404);
        }

        DB::transaction(function () use ($request, $protocol, $acao, $callback) {
            $old = $protocol->toArray();
            $callback($protocol);
            $protocol->refresh();
            AuditService::record(strtoupper($acao), $protocol, $old, $protocol->toArray());
        });

        return response()->json($protocol->fresh()->load([
            'origemUnit:id,nome,tipo',
            'destinoUnit:id,nome,tipo',
            'responsavelAtual:id,name',
            'criadoPor:id,name',
            'movements.user:id,name',
            'comments.user:id,name',
            'attachments.user:id,name',
            'notifications.user:id,name',
        ]));
    }

    private function movimentar(Protocol $protocol, string $acao, ?int $fromUnitId, ?string $statusNovo, ?int $userId, ?array $dados = null): void
    {
        ProtocolMovement::create([
            'protocol_id' => $protocol->id,
            'from_unit_id' => $fromUnitId,
            'to_unit_id' => $protocol->destino_unit_id,
            'from_user_id' => $protocol->responsavel_atual_id,
            'to_user_id' => $protocol->responsavel_atual_id,
            'acao' => $acao,
            'status_anterior' => $protocol->getOriginal('status'),
            'status_novo' => $statusNovo,
            'observacao' => $dados['observacao'] ?? null,
            'dados' => $dados,
            'user_id' => $userId,
        ]);

        if (ProtocolConfig::current()->notify_internal) {
            ProtocolNotification::create([
                'protocol_id' => $protocol->id,
                'user_id' => $protocol->responsavel_atual_id,
                'canal' => 'interna',
                'titulo' => 'Protocolo atualizado',
                'mensagem' => "Protocolo {$protocol->numero} foi {$acao}.",
                'status_envio' => 'pendente',
                'dados' => $dados,
            ]);
        }
    }

    private function recordVisualization(Protocol $protocol, ?User $user): void
    {
        ProtocolView::create([
            'protocol_id' => $protocol->id,
            'user_id' => $user?->id,
            'departamento' => $this->userDepartmentLabel($user),
            'equipe' => $this->userTeamLabel($user),
            'visualized_at' => now(),
        ]);
    }

    private function userDepartmentLabel(?User $user): ?string
    {
        $user?->loadMissing('equipeAps');
        return optional($user?->equipeAps->first())->no_equipe;
    }

    private function userTeamLabel(?User $user): ?string
    {
        $user?->loadMissing('equipeAps');
        $equipe = $user?->equipeAps->first();
        return $equipe ? trim((string) ($equipe->no_equipe ?? $equipe->nu_ine ?? '')) : null;
    }
}
