<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function laboratorio()
    {
        try {
            $data = Cache::remember('dashboard.laboratorio', 300, function () {
                try {
                    $totais = $this->service->getTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab totais: ' . $e->getMessage());
                    $totais = ['exames' => 0, 'pedidos' => 0, 'clientes' => 0, 'medicos' => 0, 'categorias' => 0, 'usuarios' => 0];
                }

                try {
                    $pedidosPorStatus = $this->service->getPedidosPorStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_status: ' . $e->getMessage());
                    $pedidosPorStatus = [];
                }

                try {
                    $pedidosPorMes = $this->service->getPedidosPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_mes: ' . $e->getMessage());
                    $pedidosPorMes = [];
                }

                try {
                    $topExames = $this->service->getTopExames();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab top_exames: ' . $e->getMessage());
                    $topExames = collect();
                }

                try {
                    $pedidosPorCategoria = $this->service->getPedidosPorCategoria();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_categoria: ' . $e->getMessage());
                    $pedidosPorCategoria = collect();
                }

                try {
                    $clientesPorMes = $this->service->getClientesPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab clientes_por_mes: ' . $e->getMessage());
                    $clientesPorMes = [];
                }

                try {
                    $resultadosStatus = $this->service->getResultadosStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab resultados_status: ' . $e->getMessage());
                    $resultadosStatus = ['liberados' => 0, 'pendentes' => 0];
                }

                try {
                    $topMedicos = $this->service->getTopMedicos();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab top_medicos: ' . $e->getMessage());
                    $topMedicos = collect();
                }

                try {
                    $realizadosPorMes = $this->service->getResultadosRealizadosPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab realizados_por_mes: ' . $e->getMessage());
                    $realizadosPorMes = [];
                }

                try {
                    $realizadosPorAno = $this->service->getResultadosRealizadosPorAno();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab realizados_por_ano: ' . $e->getMessage());
                    $realizadosPorAno = [];
                }

                return [
                    'totais'                => $totais,
                    'pedidos_por_status'    => $pedidosPorStatus,
                    'pedidos_por_mes'       => $pedidosPorMes,
                    'top_exames'            => $topExames,
                    'pedidos_por_categoria' => $pedidosPorCategoria,
                    'clientes_por_mes'      => $clientesPorMes,
                    'resultados_status'     => $resultadosStatus,
                    'top_medicos'           => $topMedicos,
                    'realizados_por_mes'    => $realizadosPorMes,
                    'realizados_por_ano'    => $realizadosPorAno,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardLab cache: ' . $e->getMessage());
            $data = ['totais' => ['exames' => 0, 'pedidos' => 0, 'clientes' => 0, 'medicos' => 0, 'categorias' => 0, 'usuarios' => 0], 'pedidos_por_status' => [], 'pedidos_por_mes' => [], 'top_exames' => [], 'pedidos_por_categoria' => [], 'clientes_por_mes' => [], 'resultados_status' => ['liberados' => 0, 'pendentes' => 0], 'top_medicos' => [], 'realizados_por_mes' => [], 'realizados_por_ano' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function fila()
    {
        try {
            $data = Cache::remember('dashboard.fila', 120, function () {
                try {
                    $totais = $this->service->getFilaTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila totais: ' . $e->getMessage());
                    $totais = ['especialidades' => 0, 'total_na_fila' => 0, 'fila_7_dias' => 0, 'total_realizados' => 0];
                }

                try {
                    $especialidades = $this->service->getFilaPorEspecialidade();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila especialidades: ' . $e->getMessage());
                    $especialidades = collect();
                }

                try {
                    $entradasPorMes = $this->service->getFilaPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila por_mes: ' . $e->getMessage());
                    $entradasPorMes = [];
                }

                try {
                    $especialidadesRealizadas = $this->service->getEspecialidadesRealizadas();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila especialidades_realizadas: ' . $e->getMessage());
                    $especialidadesRealizadas = collect();
                }

                try {
                    $realizadasPorMes = $this->service->getEspecialidadesRealizadasPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila realizadas_por_mes: ' . $e->getMessage());
                    $realizadasPorMes = [];
                }

                return [
                    'totais' => [
                        'especialidades'   => $totais['especialidades'],
                        'total_fila'       => $totais['total_na_fila'],
                        'fila_7dias'       => $totais['fila_7_dias'],
                        'total_realizados' => $totais['total_realizados'],
                    ],
                    'especialidades'            => $especialidades,
                    'entradas_por_mes'          => $entradasPorMes,
                    'especialidades_realizadas' => $especialidadesRealizadas,
                    'realizadas_por_mes'        => $realizadasPorMes,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardFila cache: ' . $e->getMessage());
            $data = ['totais' => ['especialidades' => 0, 'total_fila' => 0, 'fila_7dias' => 0, 'total_realizados' => 0], 'especialidades' => [], 'entradas_por_mes' => [], 'especialidades_realizadas' => [], 'realizadas_por_mes' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function tfd()
    {
        $periodo = in_array(request()->input('periodo'), ['mes', '12meses', 'ano'])
            ? request()->input('periodo')
            : 'mes';

        $cacheKey = 'dashboard.tfd.' . $periodo . '.' . now()->format('Y-m');
        try {
            $data = Cache::remember($cacheKey, 300, function () use ($periodo) {
                try {
                    $totais = $this->service->getTfdTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd totais: ' . $e->getMessage());
                    $totais = ['total_viagens_mes' => 0, 'pessoas_transportadas_mes' => 0, 'km_rodados_mes' => 0];
                }

                try {
                    $viagensPorDia = $this->service->getViagensPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_dia: ' . $e->getMessage());
                    $viagensPorDia = collect();
                }

                try {
                    $viagensPorDiaAgendadas = $this->service->getViagensPorDiaAgendadas();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_dia_agendadas: ' . $e->getMessage());
                    $viagensPorDiaAgendadas = collect();
                }

                try {
                    $motoristas = $this->service->getTodosMotoristas($periodo);
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd motoristas: ' . $e->getMessage());
                    $motoristas = collect();
                }

                try {
                    $rotas = $this->service->getTodasRotas($periodo);
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd rotas: ' . $e->getMessage());
                    $rotas = collect();
                }

                try {
                    $viagensPorMes = $this->service->getViagensPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_mes: ' . $e->getMessage());
                    $viagensPorMes = [];
                }

                try {
                    $viagensPorAno = $this->service->getViagensPorAno();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_ano: ' . $e->getMessage());
                    $viagensPorAno = [];
                }

                return [
                    'totais' => [
                        'total_viagens'         => $totais['total_viagens_mes'],
                        'pessoas_transportadas' => $totais['pessoas_transportadas_mes'],
                        'km_rodados'            => $totais['km_rodados_mes'],
                    ],
                    'viagens_por_dia'           => $viagensPorDia,
                    'viagens_por_dia_agendadas' => $viagensPorDiaAgendadas,
                    'motoristas'      => $motoristas,
                    'rotas'           => $rotas,
                    'viagens_por_mes' => $viagensPorMes,
                    'viagens_por_ano' => $viagensPorAno,
                    'periodo'         => $periodo,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardTfd cache: ' . $e->getMessage());
            $data = ['totais' => ['total_viagens' => 0, 'pessoas_transportadas' => 0, 'km_rodados' => 0], 'viagens_por_dia' => [], 'viagens_por_dia_agendadas' => [], 'motoristas' => [], 'rotas' => [], 'viagens_por_mes' => [], 'viagens_por_ano' => [], 'periodo' => $periodo];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function logs()
    {
        try {
            $data = Cache::remember('dashboard.logs', 600, function () {
                try {
                    $totais = $this->service->getLogsTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardLogs totais: ' . $e->getMessage());
                    $totais = ['total_qr' => 0, 'total_link_publico' => 0, 'qr_mes' => 0, 'link_publico_mes' => 0];
                }

                try {
                    $qrPorDia = $this->service->getQrPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardLogs qr_por_dia: ' . $e->getMessage());
                    $qrPorDia = collect();
                }

                try {
                    $linkPorDia = $this->service->getLinkPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardLogs link_por_dia: ' . $e->getMessage());
                    $linkPorDia = collect();
                }

                return [
                    'totais' => [
                        'total_qr'   => $totais['total_qr'],
                        'total_link' => $totais['total_link_publico'],
                        'qr_mes'     => $totais['qr_mes'],
                        'link_mes'   => $totais['link_publico_mes'],
                    ],
                    'qr_por_dia'   => $qrPorDia,
                    'link_por_dia' => $linkPorDia,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardLogs cache: ' . $e->getMessage());
            $data = ['totais' => ['total_qr' => 0, 'total_link' => 0, 'qr_mes' => 0, 'link_mes' => 0], 'qr_por_dia' => [], 'link_por_dia' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function farmacia()
    {
        $janelaDias = (int) request()->input('janela_dias', 30);
        if (!in_array($janelaDias, [7, 30, 90], true)) {
            $janelaDias = 30;
        }
        $janelaMeses = (int) request()->input('janela_meses', 12);
        if (!in_array($janelaMeses, [3, 6, 12], true)) {
            $janelaMeses = 12;
        }

        $empty = [
            'totais' => [
                'medicamentos_ativos' => 0,
                'registros_status_hoje' => 0,
                'disponiveis_hoje' => 0,
                'indisponiveis_hoje' => 0,
                'taxa_disponibilidade_hoje' => 0,
                'aquisicoes_mes_atual' => 0,
                'qtd_adquirida_mes_atual' => 0,
            ],
            'status_por_dia' => [],
            'aquisicoes_por_mes' => [],
            'top_indisponiveis' => [],
            'fontes_aquisicao_mes' => [],
        ];

        $today = now()->toDateString();
        $currentMonth = now()->format('Y-m');

        try {
            $cacheKey = 'dashboard.farmacia.' . $janelaDias . '.' . $janelaMeses . '.' . now()->format('Y-m-d');
            $data = Cache::remember($cacheKey, 180, function () use ($empty, $today, $currentMonth, $janelaDias, $janelaMeses) {
                try {
                    $medicamentosAtivos = (int) DB::table('medicine_items')
                        ->whereNull('deleted_at')
                        ->where('active', true)
                        ->count();

                    $statusHoje = DB::table('medicine_daily_statuses')
                        ->whereDate('reference_date', $today);

                    $registrosStatusHoje = (int) (clone $statusHoje)->count();
                    $disponiveisHoje = (int) (clone $statusHoje)->where('availability_status', 'available')->count();
                    $indisponiveisHoje = (int) (clone $statusHoje)->where('availability_status', 'unavailable')->count();
                    $taxaDisponibilidade = $registrosStatusHoje > 0
                        ? round(($disponiveisHoje / $registrosStatusHoje) * 100, 1)
                        : 0;

                    $aquisicoesMesAtualQuery = DB::table('medicine_monthly_acquisitions')
                        ->where('reference_month', $currentMonth);

                    $aquisicoesMesAtual = (int) (clone $aquisicoesMesAtualQuery)->count();
                    $qtdAdquiridaMesAtual = (float) (clone $aquisicoesMesAtualQuery)->sum('acquired_quantity');
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia totais: ' . $e->getMessage());
                    $medicamentosAtivos = 0;
                    $registrosStatusHoje = 0;
                    $disponiveisHoje = 0;
                    $indisponiveisHoje = 0;
                    $taxaDisponibilidade = 0;
                    $aquisicoesMesAtual = 0;
                    $qtdAdquiridaMesAtual = 0;
                }

                try {
                    $statusPorDia = DB::table('medicine_daily_statuses')
                        ->select(
                            'reference_date as dia',
                            DB::raw("SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) as disponiveis"),
                            DB::raw("SUM(CASE WHEN availability_status = 'unavailable' THEN 1 ELSE 0 END) as indisponiveis"),
                            DB::raw('COUNT(*) as total')
                        )
                        ->whereDate('reference_date', '>=', now()->subDays($janelaDias - 1)->toDateString())
                        ->groupBy('reference_date')
                        ->orderBy('reference_date')
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia status_por_dia: ' . $e->getMessage());
                    $statusPorDia = collect();
                }

                try {
                    $aquisicoesPorMes = DB::table('medicine_monthly_acquisitions')
                        ->select(
                            'reference_month as mes',
                            DB::raw('COUNT(*) as registros'),
                            DB::raw('COALESCE(SUM(acquired_quantity), 0) as quantidade_total')
                        )
                        ->where('reference_month', '>=', now()->subMonths($janelaMeses - 1)->format('Y-m'))
                        ->groupBy('reference_month')
                        ->orderBy('reference_month')
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia aquisicoes_por_mes: ' . $e->getMessage());
                    $aquisicoesPorMes = collect();
                }

                try {
                    $topIndisponiveis = DB::table('medicine_daily_statuses as s')
                        ->join('medicine_items as m', 'm.id', '=', 's.medicine_item_id')
                        ->select(
                            'm.id',
                            'm.active_ingredient',
                            'm.internal_code',
                            DB::raw('COUNT(*) as dias_indisponivel')
                        )
                        ->whereDate('s.reference_date', '>=', now()->subDays($janelaDias - 1)->toDateString())
                        ->where('s.availability_status', 'unavailable')
                        ->whereNull('m.deleted_at')
                        ->groupBy('m.id', 'm.active_ingredient', 'm.internal_code')
                        ->orderByDesc('dias_indisponivel')
                        ->limit(10)
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia top_indisponiveis: ' . $e->getMessage());
                    $topIndisponiveis = collect();
                }

                try {
                    $fontesAquisicaoMes = DB::table('medicine_monthly_acquisitions')
                        ->select('source_document', DB::raw('COUNT(*) as total'))
                        ->where('reference_month', $currentMonth)
                        ->groupBy('source_document')
                        ->orderByDesc('total')
                        ->limit(8)
                        ->get()
                        ->map(function ($row) {
                            $row->source_document = $row->source_document ?: 'Sem origem';
                            return $row;
                        });
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia fontes_aquisicao_mes: ' . $e->getMessage());
                    $fontesAquisicaoMes = collect();
                }

                return [
                    'janela_dias' => $janelaDias,
                    'janela_meses' => $janelaMeses,
                    'totais' => [
                        'medicamentos_ativos' => $medicamentosAtivos,
                        'registros_status_hoje' => $registrosStatusHoje,
                        'disponiveis_hoje' => $disponiveisHoje,
                        'indisponiveis_hoje' => $indisponiveisHoje,
                        'taxa_disponibilidade_hoje' => $taxaDisponibilidade,
                        'aquisicoes_mes_atual' => $aquisicoesMesAtual,
                        'qtd_adquirida_mes_atual' => round($qtdAdquiridaMesAtual, 2),
                    ],
                    'status_por_dia' => $statusPorDia,
                    'aquisicoes_por_mes' => $aquisicoesPorMes,
                    'top_indisponiveis' => $topIndisponiveis,
                    'fontes_aquisicao_mes' => $fontesAquisicaoMes,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardFarmacia cache: ' . $e->getMessage());
            $data = $empty;
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=180');
    }

    public function inicio()
    {
        try {
            $totais = $this->service->getInicioTotais();
        } catch (\Throwable $e) {
            Log::error('DashboardInicio totais: ' . $e->getMessage());
            $totais = ['clientes' => 0, 'especialidades' => 0, 'oficios' => 0, 'portarias' => 0, 'modelos_ia' => 0];
        }

        try {
            $clientesPorMes = $this->service->getClientesPorMes();
        } catch (\Throwable $e) {
            Log::error('DashboardInicio clientes_por_mes: ' . $e->getMessage());
            $clientesPorMes = [];
        }

        try {
            $oficiosPorMes = $this->service->getOficiosPorMes();
        } catch (\Throwable $e) {
            Log::error('DashboardInicio oficios_por_mes: ' . $e->getMessage());
            $oficiosPorMes = [];
        }

        try {
            $portariasPorMes = $this->service->getPortariasPorMes();
        } catch (\Throwable $e) {
            Log::error('DashboardInicio portarias_por_mes: ' . $e->getMessage());
            $portariasPorMes = [];
        }

        return response()->json([
            'totais'           => $totais,
            'clientes_por_mes' => $clientesPorMes,
            'oficios_por_mes'  => $oficiosPorMes,
            'portarias_por_mes' => $portariasPorMes,
        ]);
    }

    public function vigilancia()
    {
        $empty = [
            'totais'               => ['estabelecimentos' => 0, 'alvaras' => 0, 'vigentes' => 0, 'vencidos' => 0, 'a_vencer' => 0, 'vencendo_em_30' => 0],
            'por_status'           => [],
            'por_nivel_risco'      => [],
            'por_mes'              => [],
            'proximos_vencimentos' => [],
        ];

        try {
            $data = Cache::remember('dashboard.vigilancia', 300, function () use ($empty) {
                try {
                    $totais = $this->service->getVigilanciaTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia totais: ' . $e->getMessage());
                    $totais = $empty['totais'];
                }

                try {
                    $porStatus = $this->service->getAlvarasPorStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_status: ' . $e->getMessage());
                    $porStatus = [];
                }

                try {
                    $porNivelRisco = $this->service->getAlvarasPorNivelRisco();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_nivel_risco: ' . $e->getMessage());
                    $porNivelRisco = [];
                }

                try {
                    $porMes = $this->service->getAlvarasPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_mes: ' . $e->getMessage());
                    $porMes = [];
                }

                try {
                    $proximosVencimentos = $this->service->getProximosVencimentos();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia proximos_vencimentos: ' . $e->getMessage());
                    $proximosVencimentos = [];
                }

                return [
                    'totais'               => $totais,
                    'por_status'           => $porStatus,
                    'por_nivel_risco'      => $porNivelRisco,
                    'por_mes'              => $porMes,
                    'proximos_vencimentos' => $proximosVencimentos,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardVigilancia cache: ' . $e->getMessage());
            $data = $empty;
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }
}
