<?php

namespace App\Http\Controllers;

use App\Models\PedidoExame;
use Illuminate\Http\Request;

class AgendaColetaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'data' => 'nullable|date',
            'inicio' => 'nullable|date',
            'fim' => 'nullable|date',
        ]);

        $data = $request->input('data');
        $inicio = $request->input('inicio');
        $fim = $request->input('fim');

        $query = PedidoExame::with(['cliente', 'exames', 'medicoSolicitante'])
            ->whereIn('status', ['solicitado', 'coletado']);

        if ($inicio && $fim) {
            $query->whereBetween('data_coleta', [$inicio, $fim]);
        } elseif ($data) {
            $query->whereDate('data_coleta', $data);
        } else {
            $data = now()->toDateString();
            $query->whereDate('data_coleta', $data);
        }

        $pedidos = $query
            ->orderBy('data_coleta')
            ->get()
            ->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'status' => $pedido->status,
                    'data_coleta' => $pedido->data_coleta,
                    'data_pedido' => $pedido->data_pedido,
                    'observacoes' => $pedido->observacoes,
                    'paciente' => [
                        'id' => $pedido->cliente?->id,
                        'nome' => $pedido->cliente?->name,
                        'cpf' => $pedido->cliente?->cpf,
                        'cns' => $pedido->cliente?->cns,
                    ],
                    'medico' => $pedido->medicoSolicitante ? [
                        'nome' => $pedido->medicoSolicitante->nome,
                        'crm' => $pedido->medicoSolicitante->crm,
                        'uf' => $pedido->medicoSolicitante->uf_crm,
                    ] : null,
                    'exames' => $pedido->exames->map(fn ($e) => [
                        'id' => $e->id,
                        'codigo' => $e->codigo,
                        'nome' => $e->nome,
                    ]),
                ];
            });

        return response()->json([
            'data' => $data,
            'inicio' => $inicio,
            'fim' => $fim,
            'total' => $pedidos->count(),
            'pedidos' => $pedidos,
        ]);
    }
}
