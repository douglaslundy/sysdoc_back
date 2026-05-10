<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoExameRequest;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PedidoExameController extends Controller
{
    private const TRANSICOES_VALIDAS = [
        'solicitado' => ['coletado', 'cancelado'],
        'coletado'   => ['em_analise', 'cancelado'],
        'em_analise' => ['liberado', 'cancelado'],
        'liberado'   => [],
        'cancelado'  => [],
    ];

    public function index(Request $request)
    {
        $query = PedidoExame::with(['cliente', 'exames', 'resultado', 'medicoSolicitante'])
            ->orderBy('data_pedido', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('data_de')) {
            $query->whereDate('data_pedido', '>=', $request->data_de);
        }

        if ($request->filled('data_ate')) {
            $query->whereDate('data_pedido', '<=', $request->data_ate);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->whereHas('cliente', function ($q) use ($busca) {
                $q->where('name', 'LIKE', "%{$busca}%")
                  ->orWhere('cns', 'LIKE', "%{$busca}%")
                  ->orWhere('cpf', 'LIKE', "%{$busca}%");
            });
        }

        $pedidos = $query->paginate($request->input('per_page', 20));

        return response()->json($pedidos);
    }

    public function store(StorePedidoExameRequest $request)
    {
        DB::beginTransaction();
        try {
            $pedido = new PedidoExame;
            $pedido->client_id             = $request->input('client_id');
            $pedido->criado_por            = Auth::id();
            $pedido->medico_solicitante_id = $request->input('medico_solicitante_id');
            $pedido->data_pedido        = $request->input('data_pedido');
            $pedido->data_coleta        = $request->input('data_coleta');
            $pedido->status             = 'solicitado';
            $pedido->observacoes        = $request->input('observacoes');
            $pedido->save();

            $pedido->exames()->sync($request->input('exames'));

            $protocolo = ResultadoExame::gerarProtocolo();
            $senha = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            ResultadoExame::create([
                'pedido_exame_id' => $pedido->id,
                'protocolo'       => $protocolo,
                'senha_hash'      => Hash::make($senha),
                'ativo'           => true,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message'   => 'Pedido criado com sucesso!',
            'pedido'    => $pedido->load(['cliente', 'exames', 'medicoSolicitante']),
            'protocolo' => $protocolo,
            'senha'     => $senha,
        ], 201);
    }

    public function show($id)
    {
        $pedido = PedidoExame::with(['cliente', 'exames.campos.referencias', 'resultado.campos.campo', 'criadoPor'])->find($id);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }
        return response()->json($pedido);
    }

    public function atualizarStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:solicitado,coletado,em_analise,liberado,cancelado',
        ]);

        $pedido = PedidoExame::find($id);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $statusAtual = $pedido->status;
        $novoStatus  = $request->input('status');

        if (!in_array($novoStatus, self::TRANSICOES_VALIDAS[$statusAtual] ?? [])) {
            return response()->json([
                'error' => "Transição inválida: {$statusAtual} → {$novoStatus}",
            ], 422);
        }

        $pedido->status = $novoStatus;
        $pedido->save();

        return response()->json([
            'message' => 'Status atualizado com sucesso!',
            'pedido'  => $pedido,
        ]);
    }

    public function destroy($id)
    {
        $pedido = PedidoExame::find($id);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }
        $pedido->delete();
        return response()->json(['message' => 'Pedido removido com sucesso!']);
    }
}
