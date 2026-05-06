<?php

namespace App\Services;

use App\Models\CategoriaExame;
use App\Models\Client;
use App\Models\Exame;
use App\Models\MedicoSolicitante;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getTotais(): array
    {
        return [
            'exames'     => Exame::where('ativo', true)->count(),
            'pedidos'    => PedidoExame::whereNull('deleted_at')->count(),
            'clientes'   => Client::where('active', true)->count(),
            'medicos'    => MedicoSolicitante::where('ativo', true)->count(),
            'categorias' => CategoriaExame::where('ativo', true)->count(),
            'usuarios'   => User::where('active', true)->count(),
        ];
    }

    public function getPedidosPorStatus(): array
    {
        return PedidoExame::whereNull('deleted_at')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getPedidosPorMes(): array
    {
        return PedidoExame::whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();
    }

    public function getTopExames(): \Illuminate\Support\Collection
    {
        return DB::table('pedido_exame_itens')
            ->join('exames', 'pedido_exame_itens.exame_id', '=', 'exames.id')
            ->select('exames.nome', 'exames.codigo', DB::raw('count(*) as total'))
            ->groupBy('exames.id', 'exames.nome', 'exames.codigo')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function getPedidosPorCategoria(): \Illuminate\Support\Collection
    {
        return DB::table('pedido_exame_itens')
            ->join('exames', 'pedido_exame_itens.exame_id', '=', 'exames.id')
            ->join('categoria_exames', 'exames.categoria_exame_id', '=', 'categoria_exames.id')
            ->select('categoria_exames.nome', DB::raw('count(*) as total'))
            ->groupBy('categoria_exames.id', 'categoria_exames.nome')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    public function getClientesPorMes(): array
    {
        return Client::where('active', true)
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();
    }

    public function getResultadosStatus(): array
    {
        return [
            'liberados' => ResultadoExame::where('ativo', true)->whereNotNull('data_liberacao')->count(),
            'pendentes' => PedidoExame::whereNull('deleted_at')
                ->whereIn('status', ['solicitado', 'coletado', 'em_analise'])
                ->count(),
        ];
    }

    public function getTopMedicos(): \Illuminate\Support\Collection
    {
        return DB::table('pedidos_exame')
            ->join('medicos_solicitantes', 'pedidos_exame.medico_solicitante_id', '=', 'medicos_solicitantes.id')
            ->whereNull('pedidos_exame.deleted_at')
            ->select('medicos_solicitantes.nome', DB::raw('count(*) as total'))
            ->groupBy('medicos_solicitantes.id', 'medicos_solicitantes.nome')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Seção: Fila / Atendimento
    // Tabela principal: queue
    // Coluna de situação: done (boolean) — done=true significa "realizado"
    // Coluna de prioridade: urgency (boolean) — urgency=true = urgente
    // -------------------------------------------------------------------------

    public function getFilaTotais(): array
    {
        return [
            'total_na_fila'     => DB::table('queue')->where('done', false)->count(),
            'fila_7_dias'       => DB::table('queue')->where('created_at', '>=', now()->subDays(7))->count(),
            'total_realizados'  => DB::table('queue')->where('done', true)->count(),
        ];
    }

    public function getFilaPorEspecialidade(): \Illuminate\Support\Collection
    {
        return DB::table('queue')
            ->join('specialities', 'queue.id_specialities', '=', 'specialities.id')
            ->select(
                'specialities.name as nome',
                DB::raw('SUM(CASE WHEN queue.urgency = 0 THEN 1 ELSE 0 END) as normal'),
                DB::raw('SUM(CASE WHEN queue.urgency = 1 THEN 1 ELSE 0 END) as urgente')
            )
            ->groupBy('specialities.id', 'specialities.name')
            ->orderByDesc(DB::raw('normal + urgente'))
            ->get();
    }

    public function getFilaPorMes(): array
    {
        return DB::table('queue')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Seção: TFD / Viagens
    // Tabela principal: trips
    // Passageiros contados via trip_clients (sem coluna de quantidade direta)
    // Motorista: driver_id referencia users; coluna is_driver=true na tabela users
    // KM: rota tem coluna distance (km por trajeto); multiplicado por 2 (ida e volta)
    //     não há coluna km direto na trips, então somamos routes.distance por viagem
    // -------------------------------------------------------------------------

    public function getTfdTotais(): array
    {
        $mesAtual   = now()->month;
        $anoAtual   = now()->year;

        $totalViagens = DB::table('trips')
            ->whereMonth('departure_date', $mesAtual)
            ->whereYear('departure_date', $anoAtual)
            ->count();

        // Passageiros = contagem de registros em trip_clients para as trips do mês
        $pessoasTransportadas = DB::table('trip_clients')
            ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
            ->whereMonth('trips.departure_date', $mesAtual)
            ->whereYear('trips.departure_date', $anoAtual)
            ->count();

        // KM rodados = soma da distância das rotas para as trips do mês
        // Não há coluna km direta na trips; usamos routes.distance como distância por viagem
        $kmRodados = DB::table('trips')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->whereMonth('trips.departure_date', $mesAtual)
            ->whereYear('trips.departure_date', $anoAtual)
            ->sum('routes.distance');

        return [
            'total_viagens_mes'         => $totalViagens,
            'pessoas_transportadas_mes' => $pessoasTransportadas,
            'km_rodados_mes'            => (int) $kmRodados,
        ];
    }

    public function getViagensPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('trips')
            ->whereMonth('departure_date', now()->month)
            ->whereYear('departure_date', now()->year)
            ->whereNotNull('departure_date')
            ->select(DB::raw('DAY(departure_date) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function getViagensPorMotorista(): \Illuminate\Support\Collection
    {
        // Motorista armazenado em trips.driver_id (users.is_driver = true)
        return DB::table('trips')
            ->join('users', 'trips.driver_id', '=', 'users.id')
            ->select('users.name as nome', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function getTopRotas(): \Illuminate\Support\Collection
    {
        return DB::table('trips')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->select(
                DB::raw('CONCAT(routes.origin, " X ", routes.destination) as rota'),
                DB::raw('count(*) as total')
            )
            ->groupBy('routes.id', 'routes.origin', 'routes.destination')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function getViagensPorMes(): array
    {
        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $data = now()->subMonths($i);
            $ano  = $data->year;
            $mes  = $data->month;

            $viagens = DB::table('trips')
                ->whereYear('departure_date', $ano)
                ->whereMonth('departure_date', $mes)
                ->count();

            $pessoas = DB::table('trip_clients')
                ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
                ->whereYear('trips.departure_date', $ano)
                ->whereMonth('trips.departure_date', $mes)
                ->count();

            $km = (int) DB::table('trips')
                ->join('routes', 'trips.route_id', '=', 'routes.id')
                ->whereYear('trips.departure_date', $ano)
                ->whereMonth('trips.departure_date', $mes)
                ->sum('routes.distance');

            $resultado[] = [
                'mes'     => $data->format('Y-m'),
                'viagens' => $viagens,
                'pessoas' => $pessoas,
                'km'      => $km,
            ];
        }
        return $resultado;
    }

    public function getViagensPorAno(): array
    {
        $resultado = [];
        $anoAtual  = now()->year;
        for ($i = 4; $i >= 0; $i--) {
            $ano = $anoAtual - $i;

            $viagens = DB::table('trips')
                ->whereYear('departure_date', $ano)
                ->count();

            $pessoas = DB::table('trip_clients')
                ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
                ->whereYear('trips.departure_date', $ano)
                ->count();

            $km = (int) DB::table('trips')
                ->join('routes', 'trips.route_id', '=', 'routes.id')
                ->whereYear('trips.departure_date', $ano)
                ->sum('routes.distance');

            $resultado[] = [
                'ano'     => (string) $ano,
                'viagens' => $viagens,
                'pessoas' => $pessoas,
                'km'      => $km,
            ];
        }
        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Seção: Logs / QR Code
    // Tabelas: qrcode_logs (acessos via QR code), public_queue_logs (link público)
    // A tabela qrcode_logs NÃO possui coluna de tipo/source para distinguir
    // "link_publico"; essa origem é registrada separadamente em public_queue_logs.
    // -------------------------------------------------------------------------

    public function getLogsTotais(): array
    {
        $mesAtual = now()->month;
        $anoAtual = now()->year;

        return [
            'total_qr'           => DB::table('qrcode_logs')->count(),
            'total_link_publico' => DB::table('public_queue_logs')->count(),
            'qr_mes'             => DB::table('qrcode_logs')
                ->whereMonth('accessed_at', $mesAtual)
                ->whereYear('accessed_at', $anoAtual)
                ->count(),
            'link_publico_mes'   => DB::table('public_queue_logs')
                ->whereMonth('accessed_at', $mesAtual)
                ->whereYear('accessed_at', $anoAtual)
                ->count(),
        ];
    }

    public function getQrPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('qrcode_logs')
            ->whereMonth('accessed_at', now()->month)
            ->whereYear('accessed_at', now()->year)
            ->select(DB::raw('DAY(accessed_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function getLinkPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('public_queue_logs')
            ->whereMonth('accessed_at', now()->month)
            ->whereYear('accessed_at', now()->year)
            ->select(DB::raw('DAY(accessed_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }
}
