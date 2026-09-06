<?php

namespace App\Http\Controllers;

use App\Models\PainelEsusPresence;
use App\Models\SystemNotice;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends MonitorApsBaseController
{
    public function __construct(private DashboardService $service)
    {
    }

    public function laboratorio()
    {
        try {
            $data = Cache::remember('dashboard.laboratorio', 300, function () {
                try {
                    $totais = $this->service->getTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab totais: '.$e->getMessage());
                    $totais = ['exames' => 0, 'pedidos' => 0, 'clientes' => 0, 'medicos' => 0, 'categorias' => 0, 'usuarios' => 0];
                }

                try {
                    $pedidosPorStatus = $this->service->getPedidosPorStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_status: '.$e->getMessage());
                    $pedidosPorStatus = [];
                }

                try {
                    $pedidosPorMes = $this->service->getPedidosPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_mes: '.$e->getMessage());
                    $pedidosPorMes = [];
                }

                try {
                    $topExames = $this->service->getTopExames();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab top_exames: '.$e->getMessage());
                    $topExames = collect();
                }

                try {
                    $pedidosPorCategoria = $this->service->getPedidosPorCategoria();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab pedidos_por_categoria: '.$e->getMessage());
                    $pedidosPorCategoria = collect();
                }

                try {
                    $clientesPorMes = $this->service->getClientesPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab clientes_por_mes: '.$e->getMessage());
                    $clientesPorMes = [];
                }

                try {
                    $resultadosStatus = $this->service->getResultadosStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab resultados_status: '.$e->getMessage());
                    $resultadosStatus = ['liberados' => 0, 'pendentes' => 0];
                }

                try {
                    $topMedicos = $this->service->getTopMedicos();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab top_medicos: '.$e->getMessage());
                    $topMedicos = collect();
                }

                try {
                    $realizadosPorMes = $this->service->getResultadosRealizadosPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab realizados_por_mes: '.$e->getMessage());
                    $realizadosPorMes = [];
                }

                try {
                    $realizadosPorAno = $this->service->getResultadosRealizadosPorAno();
                } catch (\Throwable $e) {
                    Log::error('DashboardLab realizados_por_ano: '.$e->getMessage());
                    $realizadosPorAno = [];
                }

                return [
                    'totais' => $totais,
                    'pedidos_por_status' => $pedidosPorStatus,
                    'pedidos_por_mes' => $pedidosPorMes,
                    'top_exames' => $topExames,
                    'pedidos_por_categoria' => $pedidosPorCategoria,
                    'clientes_por_mes' => $clientesPorMes,
                    'resultados_status' => $resultadosStatus,
                    'top_medicos' => $topMedicos,
                    'realizados_por_mes' => $realizadosPorMes,
                    'realizados_por_ano' => $realizadosPorAno,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardLab cache: '.$e->getMessage());
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
                    Log::error('DashboardFila totais: '.$e->getMessage());
                    $totais = ['especialidades' => 0, 'total_na_fila' => 0, 'fila_7_dias' => 0, 'total_realizados' => 0];
                }

                try {
                    $especialidades = $this->service->getFilaPorEspecialidade();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila especialidades: '.$e->getMessage());
                    $especialidades = collect();
                }

                try {
                    $entradasPorMes = $this->service->getFilaPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila por_mes: '.$e->getMessage());
                    $entradasPorMes = [];
                }

                try {
                    $especialidadesRealizadas = $this->service->getEspecialidadesRealizadas();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila especialidades_realizadas: '.$e->getMessage());
                    $especialidadesRealizadas = collect();
                }

                try {
                    $realizadasPorMes = $this->service->getEspecialidadesRealizadasPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardFila realizadas_por_mes: '.$e->getMessage());
                    $realizadasPorMes = [];
                }

                return [
                    'totais' => [
                        'especialidades' => $totais['especialidades'],
                        'total_fila' => $totais['total_na_fila'],
                        'fila_7dias' => $totais['fila_7_dias'],
                        'total_realizados' => $totais['total_realizados'],
                    ],
                    'especialidades' => $especialidades,
                    'entradas_por_mes' => $entradasPorMes,
                    'especialidades_realizadas' => $especialidadesRealizadas,
                    'realizadas_por_mes' => $realizadasPorMes,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardFila cache: '.$e->getMessage());
            $data = ['totais' => ['especialidades' => 0, 'total_fila' => 0, 'fila_7dias' => 0, 'total_realizados' => 0], 'especialidades' => [], 'entradas_por_mes' => [], 'especialidades_realizadas' => [], 'realizadas_por_mes' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function tfd()
    {
        $periodo = in_array(request()->input('periodo'), ['mes', '12meses', 'ano'])
            ? request()->input('periodo')
            : 'mes';

        $cacheKey = 'dashboard.tfd.'.$periodo.'.'.now()->format('Y-m');
        try {
            $data = Cache::remember($cacheKey, 300, function () use ($periodo) {
                try {
                    $totais = $this->service->getTfdTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd totais: '.$e->getMessage());
                    $totais = ['total_viagens_mes' => 0, 'pessoas_transportadas_mes' => 0, 'km_rodados_mes' => 0];
                }

                try {
                    $viagensPorDia = $this->service->getViagensPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_dia: '.$e->getMessage());
                    $viagensPorDia = collect();
                }

                try {
                    $viagensPorDiaAgendadas = $this->service->getViagensPorDiaAgendadas();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_dia_agendadas: '.$e->getMessage());
                    $viagensPorDiaAgendadas = collect();
                }

                try {
                    $motoristas = $this->service->getTodosMotoristas($periodo);
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd motoristas: '.$e->getMessage());
                    $motoristas = collect();
                }

                try {
                    $rotas = $this->service->getTodasRotas($periodo);
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd rotas: '.$e->getMessage());
                    $rotas = collect();
                }

                try {
                    $viagensPorMes = $this->service->getViagensPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_mes: '.$e->getMessage());
                    $viagensPorMes = [];
                }

                try {
                    $viagensPorAno = $this->service->getViagensPorAno();
                } catch (\Throwable $e) {
                    Log::error('DashboardTfd viagens_por_ano: '.$e->getMessage());
                    $viagensPorAno = [];
                }

                return [
                    'totais' => [
                        'total_viagens' => $totais['total_viagens_mes'],
                        'pessoas_transportadas' => $totais['pessoas_transportadas_mes'],
                        'km_rodados' => $totais['km_rodados_mes'],
                    ],
                    'viagens_por_dia' => $viagensPorDia,
                    'viagens_por_dia_agendadas' => $viagensPorDiaAgendadas,
                    'motoristas' => $motoristas,
                    'rotas' => $rotas,
                    'viagens_por_mes' => $viagensPorMes,
                    'viagens_por_ano' => $viagensPorAno,
                    'periodo' => $periodo,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardTfd cache: '.$e->getMessage());
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
                    Log::error('DashboardLogs totais: '.$e->getMessage());
                    $totais = ['total_qr' => 0, 'total_link_publico' => 0, 'qr_mes' => 0, 'link_publico_mes' => 0];
                }

                try {
                    $qrPorDia = $this->service->getQrPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardLogs qr_por_dia: '.$e->getMessage());
                    $qrPorDia = collect();
                }

                try {
                    $linkPorDia = $this->service->getLinkPorDia();
                } catch (\Throwable $e) {
                    Log::error('DashboardLogs link_por_dia: '.$e->getMessage());
                    $linkPorDia = collect();
                }

                return [
                    'totais' => [
                        'total_qr' => $totais['total_qr'],
                        'total_link' => $totais['total_link_publico'],
                        'qr_mes' => $totais['qr_mes'],
                        'link_mes' => $totais['link_publico_mes'],
                    ],
                    'qr_por_dia' => $qrPorDia,
                    'link_por_dia' => $linkPorDia,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardLogs cache: '.$e->getMessage());
            $data = ['totais' => ['total_qr' => 0, 'total_link' => 0, 'qr_mes' => 0, 'link_mes' => 0], 'qr_por_dia' => [], 'link_por_dia' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function farmacia()
    {
        $janelaDias = (int) request()->input('janela_dias', 30);
        if (! in_array($janelaDias, [7, 30, 90], true)) {
            $janelaDias = 30;
        }
        $janelaMeses = (int) request()->input('janela_meses', 12);
        if (! in_array($janelaMeses, [3, 6, 12], true)) {
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
            'consumo_ranking' => [],
            'risco_falta' => [],
        ];

        $currentMonth = now()->format('Y-m');

        try {
            $cacheKey = 'dashboard.farmacia.v2.'.$janelaDias.'.'.$janelaMeses.'.'.now()->format('Y-m-d');
            $data = Cache::remember($cacheKey, 180, function () use ($currentMonth, $janelaDias, $janelaMeses) {
                try {
                    $medicamentosAtivos = (int) DB::table('medicine_items')
                        ->whereNull('deleted_at')
                        ->where('active', true)
                        ->count();

                    $statusAtual = DB::table('medicine_items as m')
                        ->leftJoin('medicine_daily_statuses as s', function ($join) {
                            $join->on('s.medicine_item_id', '=', 'm.id')
                                ->whereRaw('s.id = (
                                    select s2.id
                                    from medicine_daily_statuses s2
                                    where s2.medicine_item_id = m.id
                                    order by s2.reference_date desc, s2.id desc
                                    limit 1
                                )');
                        })
                        ->whereNull('m.deleted_at')
                        ->where('m.active', true);

                    $registrosStatusHoje = $medicamentosAtivos;
                    $disponiveisHoje = (int) (clone $statusAtual)
                        ->where('s.availability_status', 'available')
                        ->where('s.available_quantity', '>', 0)
                        ->count();
                    $indisponiveisHoje = max($registrosStatusHoje - $disponiveisHoje, 0);
                    $taxaDisponibilidade = $registrosStatusHoje > 0
                        ? round(($disponiveisHoje / $registrosStatusHoje) * 100, 1)
                        : 0;

                    $aquisicoesMesAtualQuery = DB::table('medicine_monthly_acquisitions')
                        ->where('reference_month', $currentMonth);

                    $aquisicoesMesAtual = (int) (clone $aquisicoesMesAtualQuery)->count();
                    $qtdAdquiridaMesAtual = (float) (clone $aquisicoesMesAtualQuery)->sum('acquired_quantity');
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia totais: '.$e->getMessage());
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
                            DB::raw("SUM(CASE WHEN availability_status = 'available' AND COALESCE(available_quantity, 0) > 0 THEN 1 ELSE 0 END) as disponiveis"),
                            DB::raw("SUM(CASE WHEN availability_status = 'unavailable' OR COALESCE(available_quantity, 0) <= 0 THEN 1 ELSE 0 END) as indisponiveis"),
                            DB::raw('COUNT(*) as total')
                        )
                        ->whereDate('reference_date', '>=', now()->subDays($janelaDias - 1)->toDateString())
                        ->groupBy('reference_date')
                        ->orderBy('reference_date')
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia status_por_dia: '.$e->getMessage());
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
                    Log::error('DashboardFarmacia aquisicoes_por_mes: '.$e->getMessage());
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
                        ->where(function ($query) {
                            $query->where('s.availability_status', 'unavailable')
                                ->orWhere('s.available_quantity', '<=', 0)
                                ->orWhereNull('s.available_quantity');
                        })
                        ->whereNull('m.deleted_at')
                        ->groupBy('m.id', 'm.active_ingredient', 'm.internal_code')
                        ->orderByDesc('dias_indisponivel')
                        ->limit(10)
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia top_indisponiveis: '.$e->getMessage());
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
                    Log::error('DashboardFarmacia fontes_aquisicao_mes: '.$e->getMessage());
                    $fontesAquisicaoMes = collect();
                }

                try {
                    $mesInicial = now()->startOfMonth();
                    $mesesFechados = [];
                    for ($i = 1; $i <= 3; $i++) {
                        $mesesFechados[] = $mesInicial->copy()->subMonths($i)->format('Y-m');
                    }

                    $statusDetalhado = DB::table('medicine_daily_statuses as s')
                        ->join('medicine_items as m', 'm.id', '=', 's.medicine_item_id')
                        ->select(
                            's.medicine_item_id',
                            'm.active_ingredient',
                            'm.concentration',
                            DB::raw("DATE_FORMAT(s.reference_date, '%Y-%m') as mes"),
                            's.reference_date',
                            's.available_quantity'
                        )
                        ->whereNull('m.deleted_at')
                        ->where('m.active', true)
                        ->whereIn(DB::raw("DATE_FORMAT(s.reference_date, '%Y-%m')"), $mesesFechados)
                        ->orderBy('s.medicine_item_id')
                        ->orderBy('s.reference_date')
                        ->get();

                    $porMedicamentoMes = [];
                    foreach ($statusDetalhado as $linha) {
                        $chave = $linha->medicine_item_id.'|'.$linha->mes;
                        if (! isset($porMedicamentoMes[$chave])) {
                            $porMedicamentoMes[$chave] = [
                                'medicine_item_id' => $linha->medicine_item_id,
                                'active_ingredient' => $linha->active_ingredient,
                                'concentration' => $linha->concentration,
                                'estoque_inicio' => (float) $linha->available_quantity,
                                'estoque_fim' => (float) $linha->available_quantity,
                            ];
                        } else {
                            $porMedicamentoMes[$chave]['estoque_fim'] = (float) $linha->available_quantity;
                        }
                    }

                    $aquisicoesPorMedicamentoMes = DB::table('medicine_monthly_acquisitions')
                        ->select('medicine_item_id', 'reference_month', DB::raw('SUM(acquired_quantity) as total'))
                        ->whereIn('reference_month', $mesesFechados)
                        ->groupBy('medicine_item_id', 'reference_month')
                        ->get()
                        ->keyBy(fn ($r) => $r->medicine_item_id.'|'.$r->reference_month);

                    $consumoPorMedicamento = [];
                    foreach ($porMedicamentoMes as $chave => $dadosMes) {
                        [$idMedicamento, $mesRef] = explode('|', $chave);
                        $aquisicao = $aquisicoesPorMedicamentoMes->get($idMedicamento.'|'.$mesRef);
                        $adquirido = $aquisicao ? (float) $aquisicao->total : 0.0;
                        $consumoMes = max(0.0, $dadosMes['estoque_inicio'] + $adquirido - $dadosMes['estoque_fim']);

                        if (! isset($consumoPorMedicamento[$idMedicamento])) {
                            $consumoPorMedicamento[$idMedicamento] = [
                                'medicine_item_id' => (int) $idMedicamento,
                                'active_ingredient' => $dadosMes['active_ingredient'],
                                'concentration' => $dadosMes['concentration'],
                                'meses_com_dado' => 0,
                                'consumo_total' => 0.0,
                            ];
                        }
                        $consumoPorMedicamento[$idMedicamento]['meses_com_dado']++;
                        $consumoPorMedicamento[$idMedicamento]['consumo_total'] += $consumoMes;
                    }

                    $estoqueAtualPorMedicamento = DB::table('medicine_items as m')
                        ->leftJoin('medicine_daily_statuses as s', function ($join) {
                            $join->on('s.medicine_item_id', '=', 'm.id')
                                ->whereRaw('s.id = (
                                    select s2.id
                                    from medicine_daily_statuses s2
                                    where s2.medicine_item_id = m.id
                                    order by s2.reference_date desc, s2.id desc
                                    limit 1
                                )');
                        })
                        ->whereNull('m.deleted_at')
                        ->where('m.active', true)
                        ->select('m.id', DB::raw('COALESCE(s.available_quantity, 0) as estoque_atual'))
                        ->get()
                        ->keyBy('id');

                    $consumoRanking = [];
                    $riscoFalta = [];
                    foreach ($consumoPorMedicamento as $idMedicamento => $dadosConsumo) {
                        $consumoMedioMensal = $dadosConsumo['consumo_total'] / $dadosConsumo['meses_com_dado'];
                        $consumoMedioDiario = $consumoMedioMensal / 30;
                        $nome = trim($dadosConsumo['active_ingredient'].' '.$dadosConsumo['concentration']);

                        $consumoRanking[] = [
                            'medicine_item_id' => $idMedicamento,
                            'nome' => $nome,
                            'consumo_medio_mensal' => round($consumoMedioMensal, 2),
                            'meses_com_dado' => $dadosConsumo['meses_com_dado'],
                        ];

                        if ($consumoMedioDiario > 0) {
                            $estoqueAtual = (float) ($estoqueAtualPorMedicamento->get($idMedicamento)->estoque_atual ?? 0);
                            $diasRestantes = $estoqueAtual / $consumoMedioDiario;
                            if ($diasRestantes < 15) {
                                $riscoFalta[] = [
                                    'medicine_item_id' => $idMedicamento,
                                    'nome' => $nome,
                                    'estoque_atual' => $estoqueAtual,
                                    'consumo_medio_diario' => round($consumoMedioDiario, 2),
                                    'dias_restantes' => round($diasRestantes, 1),
                                ];
                            }
                        }
                    }

                    usort($consumoRanking, fn ($a, $b) => $b['consumo_medio_mensal'] <=> $a['consumo_medio_mensal']);
                    $consumoRanking = array_slice($consumoRanking, 0, 10);

                    usort($riscoFalta, fn ($a, $b) => $a['dias_restantes'] <=> $b['dias_restantes']);
                } catch (\Throwable $e) {
                    Log::error('DashboardFarmacia consumo_ranking/risco_falta: '.$e->getMessage());
                    $consumoRanking = [];
                    $riscoFalta = [];
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
                    'consumo_ranking' => $consumoRanking,
                    'risco_falta' => $riscoFalta,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardFarmacia cache: '.$e->getMessage());
            $data = $empty;
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=180');
    }

    public function inicio()
    {
        $user = request()->user();
        $permissionService = app(\App\Services\Authorization\PagePermissionService::class);
        $hoje = now()->toDateString();

        $definicoes = [
            'farmacia' => [
                'label' => 'Farmácia',
                'permissao' => '/dashboard/farmacia',
                'kpi' => 'Indisponíveis Hoje',
                'calcular' => function () {
                    $ativos = DB::table('medicine_items')->whereNull('deleted_at')->where('active', true);
                    $registrosStatusHoje = (clone $ativos)->count();
                    $statusAtual = DB::table('medicine_items as m')
                        ->leftJoin('medicine_daily_statuses as s', function ($join) {
                            $join->on('s.medicine_item_id', '=', 'm.id')
                                ->whereRaw('s.id = (
                                    select s2.id
                                    from medicine_daily_statuses s2
                                    where s2.medicine_item_id = m.id
                                    order by s2.reference_date desc, s2.id desc
                                    limit 1
                                )');
                        })
                        ->whereNull('m.deleted_at')
                        ->where('m.active', true);
                    $disponiveisHoje = (int) (clone $statusAtual)
                        ->where('s.availability_status', 'available')
                        ->where('s.available_quantity', '>', 0)
                        ->count();

                    return max($registrosStatusHoje - $disponiveisHoje, 0);
                },
            ],
            'vigilancia' => [
                'label' => 'Vigilância Sanitária',
                'permissao' => '/dashboard/vigilancia',
                'kpi' => 'Alvarás Vencidos',
                'calcular' => fn () => \App\Models\Alvara::whereNotNull('vencimento_alvara')
                    ->whereDate('vencimento_alvara', '<', $hoje)
                    ->count(),
            ],
            'almoxarifado' => [
                'label' => 'Almoxarifado',
                'permissao' => '/dashboard/almoxarifado',
                'kpi' => 'Estoque Abaixo do Mínimo',
                'calcular' => fn () => DB::table('almoxarifado_estoques as e')
                    ->join('almoxarifado_produtos as p', 'p.id', '=', 'e.almoxarifado_produto_id')
                    ->whereColumn('e.quantidade_disponivel', '<', 'p.estoque_minimo')
                    ->count(),
            ],
            'protocolo' => [
                'label' => 'Protocolo',
                'permissao' => '/protocolo',
                'kpi' => 'Protocolos Vencidos',
                'calcular' => function () use ($user) {
                    $query = \App\Models\Protocol::query()
                        ->whereNull('encerrado_em')
                        ->whereNull('cancelado_em')
                        ->whereNotNull('prazo_atendimento')
                        ->whereDate('prazo_atendimento', '<', now());

                    if ((string) ($user?->profile ?? '') !== 'admin') {
                        $unitIds = \App\Models\ProtocolUserUnit::query()
                            ->where('user_id', $user->id)
                            ->where('ativo', true)
                            ->pluck('protocol_organizational_unit_id')
                            ->all();

                        $query->where(function ($sub) use ($user, $unitIds) {
                            $sub->where('responsavel_atual_id', $user->id)
                                ->orWhere('criado_por_id', $user->id);
                            if ($unitIds) {
                                $sub->orWhereIn('origem_unit_id', $unitIds)
                                    ->orWhereIn('destino_unit_id', $unitIds);
                            }
                        });
                    }

                    return $query->count();
                },
            ],
            'laboratorio' => [
                'label' => 'Laboratório',
                'permissao' => '/dashboard/laboratorio',
                'kpi' => 'Pedidos',
                'calcular' => fn () => \App\Models\PedidoExame::whereNull('deleted_at')->count(),
            ],
            'fila' => [
                'label' => 'Fila',
                'permissao' => '/dashboard/fila',
                'kpi' => 'Total na Fila',
                'calcular' => fn () => DB::table('queue')->where('done', 0)->count(),
            ],
            'tfd' => [
                'label' => 'TFD',
                'permissao' => '/dashboard/tfd',
                'kpi' => 'Pessoas Transportadas',
                'calcular' => function () {
                    $inicioDoMes = now()->startOfMonth();
                    $fimDoMes = now()->endOfMonth();

                    return DB::table('trip_clients')
                        ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
                        ->whereBetween('trips.departure_date', [$inicioDoMes, $fimDoMes])
                        ->count();
                },
            ],
        ];

        $setoresComAlerta = ['farmacia', 'vigilancia', 'almoxarifado', 'protocolo'];

        $cacheKey = 'dashboard.inicio.v3.'.$user->id.'.'.now()->format('Y-m-d-H');
        try {
            $setores = Cache::remember($cacheKey, 120, function () use ($definicoes, $user, $permissionService, $setoresComAlerta) {
                $resultado = [];
                foreach ($definicoes as $chave => $definicao) {
                    if (! $permissionService->canAccess($user, $definicao['permissao'])) {
                        continue;
                    }
                    try {
                        $valor = (int) ($definicao['calcular'])();
                    } catch (\Throwable $e) {
                        Log::error("DashboardInicio {$chave}: ".$e->getMessage());

                        continue;
                    }
                    $resultado[$chave] = [
                        'label' => $definicao['label'],
                        'kpi' => $definicao['kpi'],
                        'valor' => $valor,
                        'alerta' => in_array($chave, $setoresComAlerta, true) && $valor > 0,
                    ];
                }

                return $resultado;
            });
        } catch (\Throwable $e) {
            Log::error('DashboardInicio cache: '.$e->getMessage());
            $setores = [];
        }

        return response()->json(['setores' => $setores])->header('Cache-Control', 'private, max-age=120');
    }

    private function resolveUnidadeColumns(): array
    {
        return [
            'cnesCol' => $this->firstExistingColumn('tb_unidade_saude', ['co_cnes', 'nu_cnes', 'co_unico_saude']) ?? 'co_cnes',
            'nomeCol' => $this->firstExistingColumn('tb_unidade_saude', ['no_unidade_saude', 'ds_nome', 'no_estabelecimento']) ?? 'no_unidade_saude',
        ];
    }

    public function conformidades()
    {
        try {
            $data = Cache::remember('dashboard.conformidades', 60, function () {
                $empty = [
                    'totais' => [
                        'usuarios_total' => 0,
                        'usuarios_online' => 0,
                        'usuarios_offline' => 0,
                        'usuarios_24h' => 0,
                        'painel_total' => 0,
                        'painel_online' => 0,
                        'painel_offline' => 0,
                        'painel_24h' => 0,
                        'avisos_ativos' => 0,
                        'avisos_expirando_7d' => 0,
                        'avisos_para_todos' => 0,
                        'avisos_para_usuario' => 0,
                    ],
                    'usuarios_status' => [],
                    'painel_status' => [],
                    'usuarios_faixas' => [],
                    'painel_faixas' => [],
                    'avisos_destino' => [],
                    'recentes_usuarios' => [],
                    'recentes_paineis' => [],
                ];

                try {
                    $usuarios = User::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->get(['id', 'name', 'email']);
                    $presencasUsuarios = UserPresence::query()->get()->keyBy('user_id');
                } catch (\Throwable $e) {
                    Log::error('DashboardConformidades usuarios: '.$e->getMessage());
                    $usuarios = collect();
                    $presencasUsuarios = collect();
                }

                $usuariosOnline = 0;
                $usuarios24h = 0;
                $usuariosBuckets = [
                    'online' => 0,
                    'offline_24h' => 0,
                    'offline_7d' => 0,
                    'offline_7d_plus' => 0,
                    'sem_registro' => 0,
                ];
                $recentesUsuarios = [];

                foreach ($usuarios as $usuario) {
                    $presence = $presencasUsuarios->get($usuario->id);
                    $lastSeenAt = $presence?->last_seen_at;
                    $lastSeenTimestamp = $lastSeenAt?->getTimestamp() ?? 0;
                    $isOnline = $lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subMinutes(5));

                    if ($isOnline) {
                        $usuariosOnline++;
                        $usuariosBuckets['online']++;
                    } elseif ($lastSeenAt === null) {
                        $usuariosBuckets['sem_registro']++;
                    } elseif ($lastSeenAt->greaterThanOrEqualTo(now()->subHours(24))) {
                        $usuariosBuckets['offline_24h']++;
                    } elseif ($lastSeenAt->greaterThanOrEqualTo(now()->subDays(7))) {
                        $usuariosBuckets['offline_7d']++;
                    } else {
                        $usuariosBuckets['offline_7d_plus']++;
                    }

                    if ($lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subHours(24))) {
                        $usuarios24h++;
                    }

                    $recentesUsuarios[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'email' => $usuario->email,
                        'is_online' => $isOnline,
                        'last_seen_at' => $lastSeenAt?->toDateTimeString(),
                        'last_path' => $presence?->last_path,
                        'last_seen_timestamp' => $lastSeenTimestamp,
                    ];
                }

                try {
                    $conn = $this->db();
                    $cols = $this->resolveUnidadeColumns();
                    $unidades = collect($conn->table('tb_unidade_saude')
                        ->selectRaw("{$cols['cnesCol']} as cnes, {$cols['nomeCol']} as nome")
                        ->whereNotNull($cols['cnesCol'])
                        ->orderBy($cols['nomeCol'])
                        ->get())
                        ->unique('cnes')
                        ->values();
                    $presencasPaineis = PainelEsusPresence::query()->get()->keyBy('cnes');
                } catch (\Throwable $e) {
                    Log::error('DashboardConformidades paineis: '.$e->getMessage());
                    $unidades = collect();
                    $presencasPaineis = collect();
                }

                $painelOnline = 0;
                $painel24h = 0;
                $painelBuckets = [
                    'online' => 0,
                    'offline_24h' => 0,
                    'offline_7d' => 0,
                    'offline_7d_plus' => 0,
                    'sem_registro' => 0,
                ];
                $recentesPaineis = [];

                foreach ($unidades as $unidade) {
                    $cnes = (string) ($unidade->cnes ?? '');
                    $nome = trim((string) ($unidade->nome ?? $cnes)) ?: $cnes;
                    $presence = $presencasPaineis->get($cnes);
                    $lastSeenAt = $presence?->last_seen_at;
                    $lastSeenTimestamp = $lastSeenAt?->getTimestamp() ?? 0;
                    $isOnline = $lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subMinutes(5));

                    if ($isOnline) {
                        $painelOnline++;
                        $painelBuckets['online']++;
                    } elseif ($lastSeenAt === null) {
                        $painelBuckets['sem_registro']++;
                    } elseif ($lastSeenAt->greaterThanOrEqualTo(now()->subHours(24))) {
                        $painelBuckets['offline_24h']++;
                    } elseif ($lastSeenAt->greaterThanOrEqualTo(now()->subDays(7))) {
                        $painelBuckets['offline_7d']++;
                    } else {
                        $painelBuckets['offline_7d_plus']++;
                    }

                    if ($lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subHours(24))) {
                        $painel24h++;
                    }

                    $recentesPaineis[] = [
                        'cnes' => $cnes,
                        'nome' => $nome,
                        'panel_name' => $presence?->panel_name ?: $nome,
                        'is_online' => $isOnline,
                        'last_seen_at' => $lastSeenAt?->toDateTimeString(),
                        'last_seen_timestamp' => $lastSeenTimestamp,
                    ];
                }

                try {
                    $avisosAtivos = SystemNotice::query()
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('valid_until')
                                ->orWhereDate('valid_until', '>=', now()->toDateString());
                        })
                        ->count();

                    $avisosExpirando7d = SystemNotice::query()
                        ->where('is_active', true)
                        ->whereNotNull('valid_until')
                        ->whereBetween('valid_until', [now()->toDateString(), now()->addDays(7)->toDateString()])
                        ->count();

                    $avisosParaTodos = SystemNotice::query()->whereNull('target_user_id')->count();
                    $avisosParaUsuario = SystemNotice::query()->whereNotNull('target_user_id')->count();
                } catch (\Throwable $e) {
                    Log::error('DashboardConformidades avisos: '.$e->getMessage());
                    $avisosAtivos = 0;
                    $avisosExpirando7d = 0;
                    $avisosParaTodos = 0;
                    $avisosParaUsuario = 0;
                }

                $usuariosTotal = (int) $usuarios->count();
                $painelTotal = (int) $unidades->count();

                $usuariosOffline = max($usuariosTotal - $usuariosOnline, 0);
                $painelOffline = max($painelTotal - $painelOnline, 0);

                $recentesUsuarios = collect($recentesUsuarios)
                    ->sortByDesc('last_seen_timestamp')
                    ->take(8)
                    ->values()
                    ->map(function ($item) {
                        unset($item['last_seen_timestamp']);

                        return $item;
                    });

                $recentesPaineis = collect($recentesPaineis)
                    ->sortByDesc('last_seen_timestamp')
                    ->take(8)
                    ->values()
                    ->map(function ($item) {
                        unset($item['last_seen_timestamp']);

                        return $item;
                    });

                return [
                    'totais' => [
                        'usuarios_total' => $usuariosTotal,
                        'usuarios_online' => $usuariosOnline,
                        'usuarios_offline' => $usuariosOffline,
                        'usuarios_24h' => $usuarios24h,
                        'painel_total' => $painelTotal,
                        'painel_online' => $painelOnline,
                        'painel_offline' => $painelOffline,
                        'painel_24h' => $painel24h,
                        'avisos_ativos' => $avisosAtivos,
                        'avisos_expirando_7d' => $avisosExpirando7d,
                        'avisos_para_todos' => $avisosParaTodos,
                        'avisos_para_usuario' => $avisosParaUsuario,
                    ],
                    'usuarios_status' => [
                        ['label' => 'Online', 'total' => $usuariosOnline],
                        ['label' => 'Offline', 'total' => $usuariosOffline],
                    ],
                    'painel_status' => [
                        ['label' => 'Online', 'total' => $painelOnline],
                        ['label' => 'Offline', 'total' => $painelOffline],
                    ],
                    'usuarios_faixas' => [
                        ['label' => 'Online agora', 'total' => $usuariosBuckets['online']],
                        ['label' => 'Offline até 24h', 'total' => $usuariosBuckets['offline_24h']],
                        ['label' => 'Offline 1 a 7 dias', 'total' => $usuariosBuckets['offline_7d']],
                        ['label' => 'Offline +7 dias', 'total' => $usuariosBuckets['offline_7d_plus']],
                        ['label' => 'Sem registro', 'total' => $usuariosBuckets['sem_registro']],
                    ],
                    'painel_faixas' => [
                        ['label' => 'Online agora', 'total' => $painelBuckets['online']],
                        ['label' => 'Offline até 24h', 'total' => $painelBuckets['offline_24h']],
                        ['label' => 'Offline 1 a 7 dias', 'total' => $painelBuckets['offline_7d']],
                        ['label' => 'Offline +7 dias', 'total' => $painelBuckets['offline_7d_plus']],
                        ['label' => 'Sem registro', 'total' => $painelBuckets['sem_registro']],
                    ],
                    'avisos_destino' => [
                        ['label' => 'Para todos', 'total' => $avisosParaTodos],
                        ['label' => 'Usuário específico', 'total' => $avisosParaUsuario],
                    ],
                    'recentes_usuarios' => $recentesUsuarios,
                    'recentes_paineis' => $recentesPaineis,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardConformidades cache: '.$e->getMessage());
            $data = [
                'totais' => [
                    'usuarios_total' => 0,
                    'usuarios_online' => 0,
                    'usuarios_offline' => 0,
                    'usuarios_24h' => 0,
                    'painel_total' => 0,
                    'painel_online' => 0,
                    'painel_offline' => 0,
                    'painel_24h' => 0,
                    'avisos_ativos' => 0,
                    'avisos_expirando_7d' => 0,
                    'avisos_para_todos' => 0,
                    'avisos_para_usuario' => 0,
                ],
                'usuarios_status' => [],
                'painel_status' => [],
                'usuarios_faixas' => [],
                'painel_faixas' => [],
                'avisos_destino' => [],
                'recentes_usuarios' => [],
                'recentes_paineis' => [],
            ];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=60');
    }

    public function vigilancia()
    {
        $empty = [
            'totais' => ['estabelecimentos' => 0, 'alvaras' => 0, 'vigentes' => 0, 'vencidos' => 0, 'a_vencer' => 0, 'vencendo_em_30' => 0],
            'por_status' => [],
            'por_nivel_risco' => [],
            'por_mes' => [],
            'proximos_vencimentos' => [],
        ];

        try {
            $data = Cache::remember('dashboard.vigilancia', 300, function () use ($empty) {
                try {
                    $totais = $this->service->getVigilanciaTotais();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia totais: '.$e->getMessage());
                    $totais = $empty['totais'];
                }

                try {
                    $porStatus = $this->service->getAlvarasPorStatus();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_status: '.$e->getMessage());
                    $porStatus = [];
                }

                try {
                    $porNivelRisco = $this->service->getAlvarasPorNivelRisco();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_nivel_risco: '.$e->getMessage());
                    $porNivelRisco = [];
                }

                try {
                    $porMes = $this->service->getAlvarasPorMes();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia por_mes: '.$e->getMessage());
                    $porMes = [];
                }

                try {
                    $proximosVencimentos = $this->service->getProximosVencimentos();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia proximos_vencimentos: '.$e->getMessage());
                    $proximosVencimentos = [];
                }

                return [
                    'totais' => $totais,
                    'por_status' => $porStatus,
                    'por_nivel_risco' => $porNivelRisco,
                    'por_mes' => $porMes,
                    'proximos_vencimentos' => $proximosVencimentos,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('DashboardVigilancia cache: '.$e->getMessage());
            $data = $empty;
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function almoxarifado()
    {
        $data = [
            'totais' => [
                'produtos' => DB::table('almoxarifado_produtos')->where('ativo', true)->count(),
                'quantidade_disponivel' => (float) DB::table('almoxarifado_estoques')->sum('quantidade_disponivel'),
                'estoque_baixo' => DB::table('almoxarifado_estoques as e')
                    ->join('almoxarifado_produtos as p', 'p.id', '=', 'e.almoxarifado_produto_id')
                    ->whereColumn('e.quantidade_disponivel', '<', 'p.estoque_minimo')
                    ->count(),
                'requisicoes_pendentes' => DB::table('almoxarifado_requisicoes')
                    ->whereNotIn('status', ['entregue', 'cancelada'])
                    ->count(),
                'movimentacoes_mes' => DB::table('almoxarifado_movimentacoes')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])
                    ->count(),
            ],
            'requisicoes_status' => DB::table('almoxarifado_requisicoes')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'estoque_baixo' => DB::table('almoxarifado_estoques as e')
                ->join('almoxarifado_produtos as p', 'p.id', '=', 'e.almoxarifado_produto_id')
                ->whereColumn('e.quantidade_disponivel', '<', 'p.estoque_minimo')
                ->orderBy('e.quantidade_disponivel')
                ->limit(8)
                ->get(['p.nome', 'p.codigo_interno', 'e.quantidade_disponivel', 'p.estoque_minimo']),
            'movimentacoes_recentes' => DB::table('almoxarifado_movimentacoes as m')
                ->join('almoxarifado_produtos as p', 'p.id', '=', 'm.almoxarifado_produto_id')
                ->orderByDesc('m.created_at')
                ->limit(8)
                ->get(['m.id', 'p.nome as produto', 'm.tipo', 'm.quantidade', 'm.created_at']),
        ];

        return response()->json($data)->header('Cache-Control', 'private, max-age=120');
    }

    public function arquivo()
    {
        $data = [
            'totais' => [
                'documentos' => DB::table('documents')->whereNull('deleted_at')->count(),
                'versoes' => DB::table('document_versions')->whereNull('deleted_at')->count(),
                'tamanho_bytes' => (int) DB::table('document_versions')->whereNull('deleted_at')->sum('size_bytes'),
                'aprovacoes_registradas' => DB::table('document_approvals')->count(),
                'documentos_mes' => DB::table('documents')
                    ->whereNull('deleted_at')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])
                    ->count(),
            ],
            'por_status' => DB::table('documents')
                ->whereNull('deleted_at')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'por_sigilo' => DB::table('documents')
                ->whereNull('deleted_at')
                ->select('sigilo', DB::raw('COUNT(*) as total'))
                ->groupBy('sigilo')
                ->orderByDesc('total')
                ->get(),
            'recentes' => DB::table('documents as d')
                ->leftJoin('document_types as t', 't.id', '=', 'd.document_type_id')
                ->whereNull('d.deleted_at')
                ->orderByDesc('d.updated_at')
                ->limit(8)
                ->get(['d.id', 'd.titulo', 'd.status', 'd.sigilo', 'd.current_version_number', 'd.updated_at', 't.nome as tipo']),
        ];

        return response()->json($data)->header('Cache-Control', 'private, max-age=120');
    }
}
