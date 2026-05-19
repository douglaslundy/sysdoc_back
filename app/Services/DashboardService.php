<?php

namespace App\Services;

use App\Models\Alvara;
use App\Models\CategoriaExame;
use App\Models\Client;
use App\Models\Estabelecimento;
use App\Models\Exame;
use App\Models\Letter;
use App\Models\MedicoSolicitante;
use App\Models\Models;
use App\Models\Ordinance;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const TOP_EXAMES = 10;

    private const TOP_CATEGORIAS = 8;

    private const TOP_MEDICOS = 5;

    private const TOP_MOTORISTAS = 10;

    private const TOP_ROTAS = 10;

    public function getTotais(): array
    {
        return [
            'exames' => Exame::where('ativo', true)->count(),
            'pedidos' => PedidoExame::whereNull('deleted_at')->count(),
            'clientes' => Client::where('active', true)->count(),
            'medicos' => MedicoSolicitante::where('ativo', true)->count(),
            'categorias' => CategoriaExame::where('ativo', true)->count(),
            'usuarios' => User::where('active', true)->count(),
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
            ->limit(self::TOP_EXAMES)
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
            ->limit(self::TOP_CATEGORIAS)
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
            'liberados' => ResultadoExame::where('resultado_exames.ativo', true)
                ->whereNotNull('resultado_exames.data_liberacao')
                ->join('pedidos_exame', 'resultado_exames.pedido_exame_id', '=', 'pedidos_exame.id')
                ->whereNull('pedidos_exame.deleted_at')
                ->count(),
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
            ->limit(self::TOP_MEDICOS)
            ->get();
    }

    public function getResultadosRealizadosPorMes(): array
    {
        $desde = now()->subMonths(11)->startOfMonth();

        $dados = DB::table('pedidos_exame')
            ->whereNull('deleted_at')
            ->where('status', 'liberado')
            ->where('updated_at', '>=', $desde)
            ->select(DB::raw('DATE_FORMAT(updated_at, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $resultado[$key] = (int) ($dados[$key] ?? 0);
        }

        return $resultado;
    }

    public function getResultadosRealizadosPorAno(): array
    {
        $desde = now()->subYears(4)->startOfYear();

        return DB::table('pedidos_exame')
            ->whereNull('deleted_at')
            ->where('status', 'liberado')
            ->where('updated_at', '>=', $desde)
            ->select(DB::raw('YEAR(updated_at) as ano'), DB::raw('count(*) as total'))
            ->groupBy('ano')
            ->orderBy('ano')
            ->pluck('total', 'ano')
            ->toArray();
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
            'total_na_fila' => DB::table('queue')->where('done', 0)->count(),
            'fila_7_dias' => DB::table('queue')->where('created_at', '>=', now()->subDays(7))->count(),
            'total_realizados' => DB::table('queue')->where('done', 1)->count(),
            'especialidades' => Speciality::count(),
        ];
    }

    public function getFilaPorEspecialidade(): \Illuminate\Support\Collection
    {
        return DB::table('queue')
            ->leftJoin('specialities', 'queue.id_specialities', '=', 'specialities.id')
            ->whereNotNull('specialities.id')
            ->select(
                'specialities.id',
                'specialities.name as nome',
                DB::raw('SUM(CASE WHEN queue.urgency = 0 THEN 1 ELSE 0 END) as qtd_normal'),
                DB::raw('SUM(CASE WHEN queue.urgency = 1 THEN 1 ELSE 0 END) as qtd_urgente')
            )
            ->groupBy('specialities.id', 'specialities.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'nome' => $row->nome,
                'normal' => (int) $row->qtd_normal,
                'urgente' => (int) $row->qtd_urgente,
            ]);
    }

    public function getFilaPorMes(): array
    {
        return DB::table('queue')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();
    }

    public function getEspecialidadesRealizadas(): \Illuminate\Support\Collection
    {
        return DB::table('queue')
            ->leftJoin('specialities', 'queue.id_specialities', '=', 'specialities.id')
            ->whereNotNull('specialities.id')
            ->where('queue.done', 1)
            ->select(
                'specialities.name as nome',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('specialities.id', 'specialities.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['nome' => $r->nome, 'total' => (int) $r->total]);
    }

    public function getEspecialidadesRealizadasPorMes(): array
    {
        $desde = now()->subMonths(11)->startOfMonth();

        $dados = DB::table('queue')
            ->leftJoin('specialities', 'queue.id_specialities', '=', 'specialities.id')
            ->whereNotNull('specialities.id')
            ->where('queue.done', 1)
            ->where('queue.date_of_realized', '>=', $desde)
            ->select(
                DB::raw('DATE_FORMAT(queue.date_of_realized, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $resultado[$key] = (int) ($dados[$key] ?? 0);
        }

        return $resultado;
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
        $inicioDomes = now()->startOfMonth();
        $fimDomes = now()->endOfMonth();

        $totalViagens = DB::table('trips')
            ->whereBetween('departure_date', [$inicioDomes, $fimDomes])
            ->count();

        // Passageiros = contagem de registros em trip_clients para as trips do mês
        $pessoasTransportadas = DB::table('trip_clients')
            ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
            ->whereBetween('trips.departure_date', [$inicioDomes, $fimDomes])
            ->count();

        // KM rodados = soma da distância das rotas para as trips do mês
        // Não há coluna km direta na trips; usamos routes.distance como distância por viagem
        $kmRodados = DB::table('trips')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->whereBetween('trips.departure_date', [$inicioDomes, $fimDomes])
            ->sum('routes.distance');

        return [
            'total_viagens_mes' => $totalViagens,
            'pessoas_transportadas_mes' => $pessoasTransportadas,
            'km_rodados_mes' => (int) $kmRodados,
        ];
    }

    public function getViagensPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('trips')
            ->whereBetween('departure_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->select(DB::raw('DAY(departure_date) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function getViagensPorDiaAgendadas(): \Illuminate\Support\Collection
    {
        return DB::table('trips')
            ->where('departure_date', '>', now()->toDateString())
            ->where('departure_date', '<=', now()->endOfMonth()->toDateString())
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
            ->limit(self::TOP_MOTORISTAS)
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
            ->limit(self::TOP_ROTAS)
            ->get();
    }

    public function getTodosMotoristas(string $periodo = 'mes'): \Illuminate\Support\Collection
    {
        $query = DB::table('trips')->join('users', 'trips.driver_id', '=', 'users.id');

        if ($periodo === 'mes') {
            $query->whereBetween('departure_date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($periodo === '12meses') {
            $query->where('departure_date', '>=', now()->subMonths(11)->startOfMonth());
        } elseif ($periodo === 'ano') {
            $query->whereYear('departure_date', now()->year);
        }

        return $query
            ->select('users.name as nome', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get();
    }

    public function getTodasRotas(string $periodo = 'mes'): \Illuminate\Support\Collection
    {
        $query = DB::table('trips')->join('routes', 'trips.route_id', '=', 'routes.id');

        if ($periodo === 'mes') {
            $query->whereBetween('departure_date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($periodo === '12meses') {
            $query->where('departure_date', '>=', now()->subMonths(11)->startOfMonth());
        } elseif ($periodo === 'ano') {
            $query->whereYear('departure_date', now()->year);
        }

        return $query
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
        $desde = now()->subMonths(11)->startOfMonth();

        $viagens = DB::table('trips')
            ->where('departure_date', '>=', $desde)
            ->select(DB::raw('DATE_FORMAT(departure_date, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $pessoas = DB::table('trip_clients')
            ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
            ->where('trips.departure_date', '>=', $desde)
            ->select(DB::raw('DATE_FORMAT(trips.departure_date, "%Y-%m") as mes'), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $km = DB::table('trips')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->where('trips.departure_date', '>=', $desde)
            ->select(DB::raw('DATE_FORMAT(trips.departure_date, "%Y-%m") as mes'), DB::raw('sum(routes.distance) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $resultado[] = [
                'mes' => $key,
                'viagens' => (int) ($viagens[$key] ?? 0),
                'pessoas' => (int) ($pessoas[$key] ?? 0),
                'km' => (int) ($km[$key] ?? 0),
            ];
        }

        return $resultado;
    }

    public function getViagensPorAno(): array
    {
        $desde = now()->subYears(4)->startOfYear();

        $viagens = DB::table('trips')
            ->where('departure_date', '>=', $desde)
            ->select(DB::raw('YEAR(departure_date) as ano'), DB::raw('count(*) as total'))
            ->groupBy('ano')
            ->orderBy('ano')
            ->pluck('total', 'ano');

        $pessoas = DB::table('trip_clients')
            ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
            ->where('trips.departure_date', '>=', $desde)
            ->select(DB::raw('YEAR(trips.departure_date) as ano'), DB::raw('count(*) as total'))
            ->groupBy('ano')
            ->orderBy('ano')
            ->pluck('total', 'ano');

        $km = DB::table('trips')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->where('trips.departure_date', '>=', $desde)
            ->select(DB::raw('YEAR(trips.departure_date) as ano'), DB::raw('sum(routes.distance) as total'))
            ->groupBy('ano')
            ->orderBy('ano')
            ->pluck('total', 'ano');

        $resultado = [];
        $anoAtual = now()->year;
        for ($i = 4; $i >= 0; $i--) {
            $ano = $anoAtual - $i;
            $resultado[] = [
                'ano' => (string) $ano,
                'viagens' => (int) ($viagens[$ano] ?? 0),
                'pessoas' => (int) ($pessoas[$ano] ?? 0),
                'km' => (int) ($km[$ano] ?? 0),
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
        $inicioDomes = now()->startOfMonth();
        $fimDomes = now()->endOfMonth();

        return [
            'total_qr' => DB::table('qrcode_logs')->count(),
            'total_link_publico' => DB::table('public_queue_logs')->count(),
            'qr_mes' => DB::table('qrcode_logs')
                ->whereBetween('accessed_at', [$inicioDomes, $fimDomes])
                ->count(),
            'link_publico_mes' => DB::table('public_queue_logs')
                ->whereBetween('accessed_at', [$inicioDomes, $fimDomes])
                ->count(),
        ];
    }

    public function getQrPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('qrcode_logs')
            ->whereBetween('accessed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->select(DB::raw('DAY(accessed_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function getLinkPorDia(): \Illuminate\Support\Collection
    {
        return DB::table('public_queue_logs')
            ->whereBetween('accessed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->select(DB::raw('DAY(accessed_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Seção: Início — contadores gerais do sistema
    // -------------------------------------------------------------------------

    public function getInicioTotais(): array
    {
        return [
            'clientes' => Client::where('active', true)->count(),
            'especialidades' => Speciality::count(),
            'oficios' => Letter::count(),
            'portarias' => Ordinance::count(),
            'modelos_ia' => Models::count(),
        ];
    }

    public function getOficiosPorMes(): array
    {
        $rows = DB::table('letters')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mes"), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $chave = now()->subMonths($i)->format('Y-m');
            $resultado[$chave] = (int) ($rows[$chave] ?? 0);
        }

        return $resultado;
    }

    public function getPortariasPorMes(): array
    {
        $rows = DB::table('ordinances')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mes"), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $chave = now()->subMonths($i)->format('Y-m');
            $resultado[$chave] = (int) ($rows[$chave] ?? 0);
        }

        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Seção: Vigilância Sanitária — alvarás e estabelecimentos
    // -------------------------------------------------------------------------

    public function getVigilanciaTotais(): array
    {
        $hoje = now()->toDateString();
        $em30 = now()->addDays(30)->toDateString();
        $encerrados = ['Cassado', 'Cancelado', 'Cancelado de ofício', 'Interditado'];

        return [
            'estabelecimentos' => Estabelecimento::count(),
            'alvaras' => Alvara::count(),
            'vigentes' => Alvara::whereNotNull('vencimento_alvara')
                ->whereDate('vencimento_alvara', '>=', $hoje)
                ->whereNotIn('status', $encerrados)
                ->count(),
            'vencidos' => Alvara::whereNotNull('vencimento_alvara')
                ->whereDate('vencimento_alvara', '<', $hoje)
                ->count(),
            'vencendo_em_30' => Alvara::whereNotNull('vencimento_alvara')
                ->whereDate('vencimento_alvara', '>=', $hoje)
                ->whereDate('vencimento_alvara', '<=', $em30)
                ->whereNotIn('status', $encerrados)
                ->count(),
        ];
    }

    public function getAlvarasPorStatus(): array
    {
        return Alvara::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getAlvarasPorNivelRisco(): array
    {
        return Alvara::select('nivel_risco', DB::raw('count(*) as total'))
            ->groupBy('nivel_risco')
            ->orderBy('nivel_risco')
            ->pluck('total', 'nivel_risco')
            ->toArray();
    }

    public function getAlvarasPorMes(): array
    {
        $rows = Alvara::select(DB::raw("DATE_FORMAT(data_alvara, '%Y-%m') as mes"), DB::raw('count(*) as total'))
            ->where('data_alvara', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $chave = now()->subMonths($i)->format('Y-m');
            $resultado[$chave] = (int) ($rows[$chave] ?? 0);
        }

        return $resultado;
    }

    public function getProximosVencimentos(): array
    {
        return Alvara::with('estabelecimento:id,nome_estabelecimento')
            ->whereNotNull('vencimento_alvara')
            ->whereDate('vencimento_alvara', '>=', now()->toDateString())
            ->whereNotIn('status', ['Cassado', 'Cancelado', 'Cancelado de ofício', 'Interditado'])
            ->orderBy('vencimento_alvara')
            ->limit(10)
            ->get(['id', 'numero_alvara', 'status', 'vencimento_alvara', 'estabelecimento_id'])
            ->toArray();
    }
}
