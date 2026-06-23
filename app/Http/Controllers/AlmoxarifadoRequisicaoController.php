<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoEstoque;
use App\Models\AlmoxarifadoMovimentacao;
use App\Models\AlmoxarifadoProduto;
use App\Models\AlmoxarifadoRequisicao;
use App\Models\AlmoxarifadoRequisicaoHistorico;
use App\Models\AlmoxarifadoRequisicaoItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmoxarifadoRequisicaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AlmoxarifadoRequisicao::query()
            ->with([
                'secretaria:id,nome,sigla',
                'responsavel:id,name',
                'itens.produto:id,nome,codigo_interno',
                'historicos.user:id,name',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('solicitante', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $page = max(1, (int) $request->input('page', 1));

        return response()->json($query->paginate($perPage, ['*'], 'page', $page));
    }

    public function show(int $id): JsonResponse
    {
        $requisicao = AlmoxarifadoRequisicao::with([
            'secretaria:id,nome,sigla',
            'responsavel:id,name',
            'itens.produto:id,nome,codigo_interno',
            'historicos.user:id,name',
        ])->find($id);

        if (! $requisicao) {
            return response()->json(['message' => 'Requisição não encontrada.'], 404);
        }

        return response()->json($requisicao);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almoxarifado_secretaria_id' => ['required', 'integer', 'exists:almoxarifado_secretarias,id'],
            'solicitante' => ['required', 'string', 'max:150'],
            'data_solicitacao' => ['required', 'date'],
            'justificativa' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.almoxarifado_produto_id' => ['required', 'integer', 'exists:almoxarifado_produtos,id'],
            'itens.*.quantidade_solicitada' => ['required', 'numeric', 'min:0.001'],
            'itens.*.observacao' => ['nullable', 'string'],
        ]);

        $requisicao = DB::transaction(function () use ($validated, $request) {
            $numero = $this->nextNumero();
            $requisicao = AlmoxarifadoRequisicao::create([
                'numero' => $numero,
                'almoxarifado_secretaria_id' => $validated['almoxarifado_secretaria_id'],
                'solicitante' => $validated['solicitante'],
                'data_solicitacao' => $validated['data_solicitacao'],
                'status' => 'recebida',
                'justificativa' => $validated['justificativa'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
                'usuario_responsavel_id' => $request->user()?->id,
            ]);

            foreach ($validated['itens'] as $item) {
                AlmoxarifadoRequisicaoItem::create([
                    'almoxarifado_requisicao_id' => $requisicao->id,
                    'almoxarifado_produto_id' => $item['almoxarifado_produto_id'],
                    'quantidade_solicitada' => $item['quantidade_solicitada'],
                    'quantidade_atendida' => 0,
                    'quantidade_entregue' => 0,
                    'observacao' => $item['observacao'] ?? null,
                ]);
            }

            $this->registrarHistorico($requisicao, null, 'recebida', $request->user()?->id, 'Requisição criada.');

            return $requisicao->load([
                'secretaria:id,nome,sigla',
                'responsavel:id,name',
                'itens.produto:id,nome,codigo_interno',
                'historicos.user:id,name',
            ]);
        });

        return response()->json($requisicao, 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:recebida,em_analise,aprovada,recusada,em_separacao,em_processo_de_entrega,entregue,cancelada'],
            'observacao' => ['nullable', 'string'],
        ]);

        $requisicao = AlmoxarifadoRequisicao::with('itens')->find($id);
        if (! $requisicao) {
            return response()->json(['message' => 'Requisição não encontrada.'], 404);
        }

        $resultado = DB::transaction(function () use ($validated, $request, $requisicao) {
            $statusAnterior = $requisicao->status;
            $novoStatus = $validated['status'];

            if ($statusAnterior === $novoStatus) {
                return $requisicao;
            }

            if ($novoStatus === 'aprovada') {
                $this->reservarItens($requisicao);
            }

            if ($novoStatus === 'em_separacao') {
                $this->moverParaSeparacao($requisicao);
            }

            if ($novoStatus === 'entregue') {
                $this->darBaixaEntrega($requisicao);
            }

            if ($novoStatus === 'cancelada') {
                $this->estornarReservas($requisicao);
            }

            $requisicao->update([
                'status' => $novoStatus,
                'usuario_responsavel_id' => $request->user()?->id ?? $requisicao->usuario_responsavel_id,
                'data_atendimento' => $novoStatus === 'aprovada' && ! $requisicao->data_atendimento ? now() : $requisicao->data_atendimento,
                'data_entrega' => $novoStatus === 'entregue' ? now() : $requisicao->data_entrega,
            ]);

            $this->registrarHistorico($requisicao, $statusAnterior, $novoStatus, $request->user()?->id, $validated['observacao'] ?? null);

            return $requisicao->fresh()->load([
                'secretaria:id,nome,sigla',
                'responsavel:id,name',
                'itens.produto:id,nome,codigo_interno',
                'historicos.user:id,name',
            ]);
        });

        return response()->json($resultado);
    }

    private function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "REQ-{$year}-";
        $last = AlmoxarifadoRequisicao::where('numero', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('numero');

        $next = 1;
        if ($last && preg_match('/REQ-\d{4}-(\d+)/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function registrarHistorico(AlmoxarifadoRequisicao $requisicao, ?string $statusAnterior, string $novoStatus, ?int $userId, ?string $observacao): void
    {
        AlmoxarifadoRequisicaoHistorico::create([
            'almoxarifado_requisicao_id' => $requisicao->id,
            'status_anterior' => $statusAnterior,
            'novo_status' => $novoStatus,
            'observacao' => $observacao,
            'user_id' => $userId,
        ]);
    }

    private function estoqueDaRequisicao(AlmoxarifadoRequisicao $requisicao, int $produtoId): AlmoxarifadoEstoque
    {
        return AlmoxarifadoEstoque::firstOrCreate(
            [
                'almoxarifado_produto_id' => $produtoId,
                'almoxarifado_secretaria_id' => $requisicao->almoxarifado_secretaria_id,
            ],
            [
                'quantidade_disponivel' => 0,
                'quantidade_reservada' => 0,
                'quantidade_em_separacao' => 0,
                'quantidade_entregue' => 0,
            ]
        );
    }

    private function reservarItens(AlmoxarifadoRequisicao $requisicao): void
    {
        foreach ($requisicao->itens as $item) {
            $estoque = $this->estoqueDaRequisicao($requisicao, $item->almoxarifado_produto_id);
            if ((float) $estoque->quantidade_disponivel < (float) $item->quantidade_solicitada) {
                abort(422, "Saldo insuficiente para o produto {$item->produto?->nome}.");
            }

            $estoque->decrement('quantidade_disponivel', (float) $item->quantidade_solicitada);
            $estoque->increment('quantidade_reservada', (float) $item->quantidade_solicitada);

            $item->update(['quantidade_atendida' => $item->quantidade_solicitada]);

            AlmoxarifadoMovimentacao::create([
                'almoxarifado_produto_id' => $item->almoxarifado_produto_id,
                'almoxarifado_secretaria_origem_id' => $requisicao->almoxarifado_secretaria_id,
                'almoxarifado_secretaria_destino_id' => $requisicao->almoxarifado_secretaria_id,
                'tipo' => 'reservado',
                'quantidade' => $item->quantidade_solicitada,
                'saldo_anterior' => 0,
                'saldo_posterior' => 0,
                'motivo' => 'Reserva de requisição',
                'observacao' => $requisicao->numero,
                'documento_tipo' => AlmoxarifadoRequisicao::class,
                'documento_id' => $requisicao->id,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function moverParaSeparacao(AlmoxarifadoRequisicao $requisicao): void
    {
        foreach ($requisicao->itens as $item) {
            $estoque = $this->estoqueDaRequisicao($requisicao, $item->almoxarifado_produto_id);
            $quantidade = (float) $item->quantidade_atendida;
            if ($quantidade <= 0) {
                $quantidade = (float) $item->quantidade_solicitada;
            }

            if ((float) $estoque->quantidade_reservada < $quantidade) {
                abort(422, "Reserva insuficiente para o produto {$item->produto?->nome}.");
            }

            $estoque->decrement('quantidade_reservada', $quantidade);
            $estoque->increment('quantidade_em_separacao', $quantidade);
        }
    }

    private function darBaixaEntrega(AlmoxarifadoRequisicao $requisicao): void
    {
        foreach ($requisicao->itens as $item) {
            $estoque = $this->estoqueDaRequisicao($requisicao, $item->almoxarifado_produto_id);
            $quantidade = (float) $item->quantidade_atendida;
            if ($quantidade <= 0) {
                $quantidade = (float) $item->quantidade_solicitada;
            }

            $quantidadeSeparacao = min((float) $estoque->quantidade_em_separacao, $quantidade);
            if ($quantidadeSeparacao > 0) {
                $estoque->decrement('quantidade_em_separacao', $quantidadeSeparacao);
            }

            $restante = $quantidade - $quantidadeSeparacao;
            if ($restante > 0 && (float) $estoque->quantidade_reservada >= $restante) {
                $estoque->decrement('quantidade_reservada', $restante);
            }

            $estoque->increment('quantidade_entregue', $quantidade);

            AlmoxarifadoMovimentacao::create([
                'almoxarifado_produto_id' => $item->almoxarifado_produto_id,
                'almoxarifado_secretaria_origem_id' => $requisicao->almoxarifado_secretaria_id,
                'almoxarifado_secretaria_destino_id' => $requisicao->almoxarifado_secretaria_id,
                'tipo' => 'saida',
                'quantidade' => $quantidade,
                'saldo_anterior' => 0,
                'saldo_posterior' => 0,
                'motivo' => 'Entrega de requisição',
                'observacao' => $requisicao->numero,
                'documento_tipo' => AlmoxarifadoRequisicao::class,
                'documento_id' => $requisicao->id,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function estornarReservas(AlmoxarifadoRequisicao $requisicao): void
    {
        foreach ($requisicao->itens as $item) {
            $estoque = $this->estoqueDaRequisicao($requisicao, $item->almoxarifado_produto_id);
            $quantidade = (float) $item->quantidade_atendida;
            if ($quantidade <= 0) {
                $quantidade = (float) $item->quantidade_solicitada;
            }

            if ((float) $estoque->quantidade_reservada >= $quantidade) {
                $estoque->decrement('quantidade_reservada', $quantidade);
                $estoque->increment('quantidade_disponivel', $quantidade);
            }
        }
    }
}
