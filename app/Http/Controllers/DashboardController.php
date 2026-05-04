<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Exame;
use App\Models\MedicoSolicitante;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\CategoriaExame;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function laboratorio()
    {
        // Totalizadores
        $totais = [
            'exames'    => Exame::where('ativo', true)->count(),
            'pedidos'   => PedidoExame::whereNull('deleted_at')->count(),
            'clientes'  => Client::whereNull('deleted_at')->count(),
            'medicos'   => MedicoSolicitante::where('ativo', true)->count(),
            'categorias'=> CategoriaExame::where('ativo', true)->count(),
            'usuarios'  => User::where('active', true)->count(),
        ];

        // Pedidos por status
        $pedidosPorStatus = PedidoExame::whereNull('deleted_at')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Pedidos por mês (últimos 12 meses)
        $pedidosPorMes = PedidoExame::whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
                DB::raw('count(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        // Top 10 exames mais solicitados
        $topExames = DB::table('pedido_exame_itens')
            ->join('exames', 'pedido_exame_itens.exame_id', '=', 'exames.id')
            ->select('exames.nome', 'exames.codigo', DB::raw('count(*) as total'))
            ->groupBy('exames.id', 'exames.nome', 'exames.codigo')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Pedidos por categoria de exame
        $pedidosPorCategoria = DB::table('pedido_exame_itens')
            ->join('exames', 'pedido_exame_itens.exame_id', '=', 'exames.id')
            ->join('categoria_exames', 'exames.categoria_exame_id', '=', 'categoria_exames.id')
            ->select('categoria_exames.nome', DB::raw('count(*) as total'))
            ->groupBy('categoria_exames.id', 'categoria_exames.nome')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Clientes cadastrados por mês (últimos 12 meses)
        $clientesPorMes = Client::whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
                DB::raw('count(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        // Resultados liberados vs pendentes
        $resultadosPorStatus = [
            'liberados'  => ResultadoExame::whereNotNull('data_liberacao')->count(),
            'pendentes'  => PedidoExame::whereNull('deleted_at')
                ->whereIn('status', ['solicitado', 'coletado', 'em_analise'])
                ->count(),
        ];

        // Top 5 médicos que mais solicitaram
        $topMedicos = DB::table('pedidos_exame')
            ->join('medicos_solicitantes', 'pedidos_exame.medico_solicitante_id', '=', 'medicos_solicitantes.id')
            ->whereNull('pedidos_exame.deleted_at')
            ->select('medicos_solicitantes.nome', DB::raw('count(*) as total'))
            ->groupBy('medicos_solicitantes.id', 'medicos_solicitantes.nome')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'totais'               => $totais,
            'pedidos_por_status'   => $pedidosPorStatus,
            'pedidos_por_mes'      => $pedidosPorMes,
            'top_exames'           => $topExames,
            'pedidos_por_categoria'=> $pedidosPorCategoria,
            'clientes_por_mes'     => $clientesPorMes,
            'resultados_status'    => $resultadosPorStatus,
            'top_medicos'          => $topMedicos,
        ]);
    }
}
