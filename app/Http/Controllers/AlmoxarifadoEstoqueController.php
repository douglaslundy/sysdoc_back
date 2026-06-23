<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoEstoque;
use App\Models\AlmoxarifadoMovimentacao;
use App\Models\AlmoxarifadoProduto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmoxarifadoEstoqueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AlmoxarifadoEstoque::query()
            ->with([
                'produto:id,nome,codigo_interno,estoque_minimo,estoque_maximo,almoxarifado_categoria_id,almoxarifado_unidade_medida_id',
                'produto.categoria:id,nome',
                'produto.unidade:id,nome,sigla',
                'secretaria:id,nome,sigla',
            ])
            ->orderByDesc('quantidade_disponivel');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('produto', function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo_interno', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%");
            });
        }

        if ($request->filled('secretaria_id')) {
            $query->where('almoxarifado_secretaria_id', $request->integer('secretaria_id'));
        }

        if ($request->filled('produto_id')) {
            $query->where('almoxarifado_produto_id', $request->integer('produto_id'));
        }

        if ($request->boolean('abaixo_minimo')) {
            $query->join('almoxarifado_produtos as p', 'p.id', '=', 'almoxarifado_estoques.almoxarifado_produto_id')
                ->whereColumn('almoxarifado_estoques.quantidade_disponivel', '<', 'p.estoque_minimo')
                ->select('almoxarifado_estoques.*');
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $page = max(1, (int) $request->input('page', 1));

        return response()->json($query->paginate($perPage, ['*'], 'page', $page));
    }

    public function movimentar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almoxarifado_produto_id' => ['required', 'integer', 'exists:almoxarifado_produtos,id'],
            'almoxarifado_secretaria_id' => ['nullable', 'integer', 'exists:almoxarifado_secretarias,id'],
            'tipo' => ['required', 'in:entrada,saida,ajuste,transferencia'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'motivo' => ['required', 'string', 'max:150'],
            'observacao' => ['nullable', 'string'],
            'secretaria_destino_id' => ['nullable', 'integer', 'exists:almoxarifado_secretarias,id'],
        ]);

        $resultado = DB::transaction(function () use ($validated, $request) {
            $produto = AlmoxarifadoProduto::findOrFail($validated['almoxarifado_produto_id']);
            $origemId = $validated['almoxarifado_secretaria_id'] ?? null;
            $destinoId = $validated['secretaria_destino_id'] ?? null;
            $quantidade = (float) $validated['quantidade'];

            $estoqueOrigem = AlmoxarifadoEstoque::firstOrCreate(
                [
                    'almoxarifado_produto_id' => $produto->id,
                    'almoxarifado_secretaria_id' => $origemId,
                ],
                [
                    'quantidade_disponivel' => 0,
                    'quantidade_reservada' => 0,
                    'quantidade_em_separacao' => 0,
                    'quantidade_entregue' => 0,
                ]
            );

            $saldoAnterior = (float) $estoqueOrigem->quantidade_disponivel;
            $saldoPosterior = $saldoAnterior;

            if ($validated['tipo'] === 'entrada') {
                $saldoPosterior = $saldoAnterior + $quantidade;
                $estoqueOrigem->increment('quantidade_disponivel', $quantidade);
            } elseif ($validated['tipo'] === 'saida') {
                if ($saldoAnterior < $quantidade) {
                    abort(422, 'Saldo insuficiente para a saída solicitada.');
                }
                $saldoPosterior = $saldoAnterior - $quantidade;
                $estoqueOrigem->decrement('quantidade_disponivel', $quantidade);
            } elseif ($validated['tipo'] === 'ajuste') {
                $saldoPosterior = $quantidade;
                $estoqueOrigem->update(['quantidade_disponivel' => $quantidade]);
            } elseif ($validated['tipo'] === 'transferencia') {
                if (! $destinoId) {
                    abort(422, 'Informe a secretaria de destino para transferência.');
                }
                if ($saldoAnterior < $quantidade) {
                    abort(422, 'Saldo insuficiente para a transferência solicitada.');
                }

                $estoqueOrigem->decrement('quantidade_disponivel', $quantidade);
                $saldoPosterior = $saldoAnterior - $quantidade;

                $estoqueDestino = AlmoxarifadoEstoque::firstOrCreate(
                    [
                        'almoxarifado_produto_id' => $produto->id,
                        'almoxarifado_secretaria_id' => $destinoId,
                    ],
                    [
                        'quantidade_disponivel' => 0,
                        'quantidade_reservada' => 0,
                        'quantidade_em_separacao' => 0,
                        'quantidade_entregue' => 0,
                    ]
                );

                $estoqueDestino->increment('quantidade_disponivel', $quantidade);
            }

            $movimentacao = AlmoxarifadoMovimentacao::create([
                'almoxarifado_produto_id' => $produto->id,
                'almoxarifado_secretaria_origem_id' => $origemId,
                'almoxarifado_secretaria_destino_id' => $destinoId,
                'tipo' => $validated['tipo'],
                'quantidade' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'motivo' => $validated['motivo'],
                'observacao' => $validated['observacao'] ?? null,
                'documento_tipo' => 'manual',
                'documento_id' => null,
                'user_id' => $request->user()?->id,
            ]);

            return $movimentacao->load([
                'produto:id,nome,codigo_interno',
                'secretariaOrigem:id,nome',
                'secretariaDestino:id,nome',
                'user:id,name',
            ]);
        });

        return response()->json($resultado, 201);
    }
}
