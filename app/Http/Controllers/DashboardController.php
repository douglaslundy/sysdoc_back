<?php

namespace App\Http\Controllers;

use App\Models\PainelEsusPresence;
use App\Models\SystemNotice;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\DashboardService;
use Illuminate\Http\Request;
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
            $data = Cache::remember('dashboard.laboratorio.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 300, function () {
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
            $data = Cache::remember('dashboard.fila.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 120, function () {
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
                        'total_na_fila' => $totais['total_na_fila'],
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
            $data = ['totais' => ['especialidades' => 0, 'total_na_fila' => 0, 'fila_7dias' => 0, 'total_realizados' => 0], 'especialidades' => [], 'entradas_por_mes' => [], 'especialidades_realizadas' => [], 'realizadas_por_mes' => []];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function tfd()
    {
        $periodo = in_array(request()->input('periodo'), ['mes', '12meses', 'ano'])
            ? request()->input('periodo')
            : 'mes';

        $cacheKey = 'dashboard.tfd.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey().'.'.$periodo.'.'.now()->format('Y-m');
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
                        'pessoas_transportadas_mes' => $totais['pessoas_transportadas_mes'],
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
            $data = ['totais' => ['total_viagens' => 0, 'pessoas_transportadas_mes' => 0, 'km_rodados' => 0], 'viagens_por_dia' => [], 'viagens_por_dia_agendadas' => [], 'motoristas' => [], 'rotas' => [], 'viagens_por_mes' => [], 'viagens_por_ano' => [], 'periodo' => $periodo];
        }

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function logs()
    {
        try {
            $data = Cache::remember('dashboard.logs.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 600, function () {
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
            $cacheKey = 'dashboard.farmacia.v3.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey().'.'.$janelaDias.'.'.$janelaMeses.'.'.now()->format('Y-m-d');
            $data = Cache::remember($cacheKey, 180, function () use ($currentMonth, $janelaDias, $janelaMeses) {
                try {
                    $medicamentosAtivos = (int) \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_items'), 'secretaria_id')
                        ->whereNull('deleted_at')
                        ->where('active', true)
                        ->count();

                    $statusAtual = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_items as m'), 'm.secretaria_id')
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

                    $aquisicoesMesAtualQuery = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_monthly_acquisitions'), 'secretaria_id')
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
                    $statusPorDia = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_daily_statuses'), 'secretaria_id')
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
                    $aquisicoesPorMes = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_monthly_acquisitions'), 'secretaria_id')
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
                    $topIndisponiveis = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_daily_statuses as s'), 's.secretaria_id')
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
                    $fontesAquisicaoMes = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_monthly_acquisitions'), 'secretaria_id')
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

                    $statusDetalhado = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_daily_statuses as s'), 's.secretaria_id')
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

                    $aquisicoesPorMedicamentoMes = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_monthly_acquisitions'), 'secretaria_id')
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

                    $estoqueAtualPorMedicamento = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_items as m'), 'm.secretaria_id')
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
                    $ativos = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_items'), 'secretaria_id')->whereNull('deleted_at')->where('active', true);
                    $registrosStatusHoje = (clone $ativos)->count();
                    $statusAtual = \App\Support\EscopoAlmoxarifado::filtrar(DB::table('medicine_items as m'), 'm.secretaria_id')
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
                // Eloquent: o escopo de secretaria vale (DB::table ignoraria).
                'calcular' => fn () => \App\Models\AlmoxarifadoEstoque::query()
                    ->join('almoxarifado_produtos as p', 'p.id', '=', 'almoxarifado_estoques.almoxarifado_produto_id')
                    ->whereColumn('almoxarifado_estoques.quantidade_disponivel', '<', 'p.estoque_minimo')
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
                                $sub->orWhere(function ($unit) use ($unitIds) {
                                    $unit->whereNull('responsavel_atual_id')
                                        ->where(fn ($u) => $u->whereIn('origem_unit_id', $unitIds)->orWhereIn('destino_unit_id', $unitIds));
                                });
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
                'calcular' => fn () => \App\Support\EscopoAlmoxarifado::filtrar(DB::table('queue'), 'secretaria_id')->where('done', 0)->count(),
            ],
            'tfd' => [
                'label' => 'TFD',
                'permissao' => '/dashboard/tfd',
                'kpi' => 'Pessoas Transportadas',
                'calcular' => function () {
                    $inicioDoMes = now()->startOfMonth();
                    $fimDoMes = now()->endOfMonth();

                    return \App\Support\EscopoAlmoxarifado::filtrar(DB::table('trip_clients'), 'trips.secretaria_id')
                        ->join('trips', 'trip_clients.trip_id', '=', 'trips.id')
                        ->whereBetween('trips.departure_date', [$inicioDoMes, $fimDoMes])
                        ->count();
                },
            ],
        ];

        $setoresComAlerta = ['farmacia', 'vigilancia', 'almoxarifado', 'protocolo'];

        $cacheKey = 'dashboard.inicio.v4.'.$user->id.'.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey().'.'.now()->format('Y-m-d-H');
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
            $data = Cache::remember('dashboard.conformidades.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 60, function () {
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
                    // Só quem atende a secretaria ativa (admin sempre entra); "Todas" vê todos.
                    $ativa = app(\App\Services\Secretarias\ActiveSecretaria::class);
                    $usuarios = User::query()
                        ->where('active', true)
                        ->when(! $ativa->isAll(), fn ($q) => \App\Services\Secretarias\SecretariaMembership::filtrar($q, (int) $ativa->id()))
                        ->orderBy('name')
                        ->get(['id', 'name', 'email']);
                    $presencasUsuarios = UserPresence::query()->whereIn('user_id', $usuarios->pluck('id'))->get()->keyBy('user_id');
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
            'fiscalizacoes' => ['no_ano' => 0, 'no_mes' => 0, 'denuncias_pendentes' => 0, 'autos_infracao_ano' => 0],
        ];

        try {
            // v3: cache por secretaria (v2 era global e serviria numeros de outra secretaria).
            $data = Cache::remember('dashboard.vigilancia.v3.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 300, function () use ($empty) {
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

                try {
                    $fiscalizacoes = $this->service->getFiscalizacoesKpis();
                } catch (\Throwable $e) {
                    Log::error('DashboardVigilancia fiscalizacoes: '.$e->getMessage());
                    $fiscalizacoes = $empty['fiscalizacoes'];
                }

                return [
                    'totais' => $totais,
                    'fiscalizacoes' => $fiscalizacoes,
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
        // Eloquent (escopo de secretaria) e cache por secretaria: nada de contagem de outra secretaria.
        $data = Cache::remember('dashboard.almoxarifado.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 60, fn () => [
            'totais' => [
                'produtos' => \App\Models\AlmoxarifadoProduto::where('ativo', true)->count(),
                'quantidade_disponivel' => (float) \App\Models\AlmoxarifadoEstoque::sum('quantidade_disponivel'),
                'estoque_baixo' => \App\Models\AlmoxarifadoEstoque::query()
                    ->join('almoxarifado_produtos as p', 'p.id', '=', 'almoxarifado_estoques.almoxarifado_produto_id')
                    ->whereColumn('almoxarifado_estoques.quantidade_disponivel', '<', 'p.estoque_minimo')
                    ->count(),
                'requisicoes_pendentes' => \App\Models\AlmoxarifadoRequisicao::whereNotIn('status', ['entregue', 'cancelada'])->count(),
                'movimentacoes_mes' => \App\Models\AlmoxarifadoMovimentacao::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            ],
            'requisicoes_status' => \App\Models\AlmoxarifadoRequisicao::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'estoque_baixo' => \App\Models\AlmoxarifadoEstoque::query()
                ->join('almoxarifado_produtos as p', 'p.id', '=', 'almoxarifado_estoques.almoxarifado_produto_id')
                ->whereColumn('almoxarifado_estoques.quantidade_disponivel', '<', 'p.estoque_minimo')
                ->orderBy('almoxarifado_estoques.quantidade_disponivel')
                ->limit(8)
                ->get(['p.nome', 'p.codigo_interno', 'almoxarifado_estoques.quantidade_disponivel', 'p.estoque_minimo']),
            'movimentacoes_recentes' => \App\Models\AlmoxarifadoMovimentacao::query()
                ->join('almoxarifado_produtos as p', 'p.id', '=', 'almoxarifado_movimentacoes.almoxarifado_produto_id')
                ->orderByDesc('almoxarifado_movimentacoes.created_at')
                ->limit(8)
                ->get(['almoxarifado_movimentacoes.id', 'p.nome as produto', 'almoxarifado_movimentacoes.tipo', 'almoxarifado_movimentacoes.quantidade', 'almoxarifado_movimentacoes.created_at']),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, max-age=120');
    }

    public function arquivo()
    {
        // Eloquent (escopo de secretaria) e chave de cache por secretaria: nada de contagem de outra secretaria.
        $data = Cache::remember('dashboard.arquivo.'.app(\App\Services\Secretarias\ActiveSecretaria::class)->cacheKey(), 60, fn () => [
            'totais' => [
                'documentos' => \App\Models\Document::count(),
                'versoes' => \App\Models\DocumentVersion::count(),
                'tamanho_bytes' => (int) \App\Models\DocumentVersion::sum('size_bytes'),
                'aprovacoes_registradas' => \App\Models\DocumentApproval::count(),
                'documentos_mes' => \App\Models\Document::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            ],
            'por_status' => \App\Models\Document::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'por_sigilo' => \App\Models\Document::select('sigilo', DB::raw('COUNT(*) as total'))
                ->groupBy('sigilo')
                ->orderByDesc('total')
                ->get(),
            'recentes' => \App\Models\Document::query()
                ->leftJoin('document_types as t', 't.id', '=', 'documents.document_type_id')
                ->orderByDesc('documents.updated_at')
                ->limit(8)
                ->get(['documents.id', 'documents.titulo', 'documents.status', 'documents.sigilo', 'documents.current_version_number', 'documents.updated_at', 't.nome as tipo']),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, max-age=120');
    }

    /** Métricas das ordens de serviço visíveis ao usuário, no período (`dias`, padrão 30). */
    public function ordensServico(Request $request)
    {
        $user = $request->user();
        $dias = in_array((int) $request->query('dias'), [7, 30, 90, 365], true) ? (int) $request->query('dias') : 30;
        $desde = now()->subDays($dias)->startOfDay();
        $base = fn () => \App\Models\OrdemServico::visiveisPara($user)->where('ordens_servico.created_at', '>=', $desde);

        $porStatus = $base()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $concluidas = $base()->where('status', 'concluida')->whereNotNull('concluida_em');
        $minutos = (float) (clone $concluidas)->avg(DB::raw('TIMESTAMPDIFF(MINUTE, ordens_servico.created_at, ordens_servico.concluida_em)'));
        $noPrazo = (clone $concluidas)->whereNotNull('prazo')->whereRaw('DATE(concluida_em) <= prazo')->count();
        $comPrazo = (clone $concluidas)->whereNotNull('prazo')->count();

        $data = [
            'periodo_dias' => $dias,
            'totais' => [
                'total' => (int) $porStatus->sum(),
                'abertas' => (int) (($porStatus['aberta'] ?? 0) + ($porStatus['atribuida'] ?? 0)),
                'em_atendimento' => (int) ($porStatus['em_atendimento'] ?? 0),
                'concluidas' => (int) ($porStatus['concluida'] ?? 0),
                'canceladas' => (int) ($porStatus['cancelada'] ?? 0),
                'atrasadas' => $base()->whereNotIn('status', \App\Models\OrdemServico::ENCERRADAS)->whereNotNull('prazo')->whereDate('prazo', '<', now()->toDateString())->count(),
            ],
            'tempo_medio_resolucao_horas' => $minutos > 0 ? round($minutos / 60, 1) : 0,
            'tempo_medio_atendimento_min' => (int) round((float) (clone $concluidas)->whereNotNull('tempo_gasto_min')->avg('tempo_gasto_min')),
            'taxa_no_prazo' => $comPrazo > 0 ? round($noPrazo * 100 / $comPrazo, 1) : null,
            'por_status' => $porStatus->map(fn ($total, $status) => ['status' => $status, 'total' => (int) $total])->values(),
            'por_prioridade' => $base()->select('prioridade', DB::raw('COUNT(*) as total'))->groupBy('prioridade')->orderByDesc('total')->get(),
            'por_tipo' => $base()->join('os_tipos_servico as t', 't.id', '=', 'ordens_servico.tipo_servico_id')
                ->select('t.nome', DB::raw('COUNT(*) as total'))->groupBy('t.nome')->orderByDesc('total')->limit(8)->get(),
            'por_tecnico' => $base()->join('users as u', 'u.id', '=', 'ordens_servico.tecnico_id')
                ->select('u.name as nome', DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(ordens_servico.status = 'concluida') as concluidas"),
                    DB::raw("AVG(CASE WHEN ordens_servico.status = 'concluida' AND ordens_servico.concluida_em IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, ordens_servico.created_at, ordens_servico.concluida_em) END) / 60 as horas_medias"))
                ->groupBy('u.id', 'u.name')->orderByDesc('total')->limit(10)->get()
                ->map(fn ($r) => ['nome' => $r->nome, 'total' => (int) $r->total, 'concluidas' => (int) $r->concluidas, 'horas_medias' => $r->horas_medias === null ? null : round((float) $r->horas_medias, 1)]),
            'por_dia' => $base()->select(DB::raw('DATE(ordens_servico.created_at) as dia'), DB::raw('COUNT(*) as abertas'))
                ->groupBy('dia')->orderBy('dia')->get(),
        ];

        return response()->json($data)->header('Cache-Control', 'private, max-age=60');
    }

    /**
     * Métricas dos protocolos que o usuário vê na caixa de entrada (admin: todos), no período (`dias`, padrão 30).
     * Situação atual (abertos, vencidos, parados) não depende do período.
     */
    public function protocolos(Request $request)
    {
        $user = $request->user();
        $dias = in_array((int) $request->query('dias'), [7, 30, 90, 365], true) ? (int) $request->query('dias') : 30;
        $desde = now()->subDays($dias)->startOfDay();
        $hoje = now()->toDateString();
        $encerrados = ['encerrado', 'concluido', 'cancelado'];
        $todos = fn () => \App\Models\Protocol::visiveis($user)->visiveisPara($user);
        $periodo = fn () => $todos()->where('protocols.created_at', '>=', $desde);
        $abertos = fn () => $todos()->whereNotIn('protocols.status', $encerrados);
        $fim = 'COALESCE(protocols.encerrado_em, protocols.updated_at)';

        $porStatus = $periodo()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $concluidos = $periodo()->whereIn('protocols.status', ['encerrado', 'concluido']);
        $horasConclusao = (float) (clone $concluidos)->avg(DB::raw("TIMESTAMPDIFF(MINUTE, protocols.created_at, {$fim})")) / 60;
        $horasRecebimento = (float) $periodo()->whereNotNull('protocols.recebido_em')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, protocols.created_at, protocols.recebido_em)')) / 60;
        $comPrazo = (clone $concluidos)->whereNotNull('protocols.prazo_atendimento');
        $noPrazo = (clone $comPrazo)->whereRaw("DATE({$fim}) <= DATE(protocols.prazo_atendimento)")->count();
        $totalComPrazo = (clone $comPrazo)->count();

        // Ranking do que está em aberto agora: volume, vencidos e dias parado (desde a última atualização).
        $ranking = fn ($q, string $nome, string $chave) => $q
            ->select(DB::raw("{$nome} as nome"), DB::raw('COUNT(*) as abertos'),
                DB::raw("SUM(protocols.prazo_atendimento IS NOT NULL AND DATE(protocols.prazo_atendimento) < '{$hoje}') as vencidos"),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, protocols.updated_at, NOW())) / 24 as dias_parado'))
            ->groupBy($chave, DB::raw($nome))->orderByDesc('abertos')->limit(10)->get()
            ->map(fn ($r) => ['nome' => $r->nome, 'abertos' => (int) $r->abertos, 'vencidos' => (int) $r->vencidos, 'dias_parado' => round((float) $r->dias_parado, 1)]);

        $data = [
            'periodo_dias' => $dias,
            'totais' => [
                'total' => (int) $porStatus->sum(),
                'concluidos' => (int) (($porStatus['encerrado'] ?? 0) + ($porStatus['concluido'] ?? 0)),
                'cancelados' => (int) ($porStatus['cancelado'] ?? 0),
                'abertos' => $abertos()->count(),
                'novos' => $abertos()->where('protocols.novo', true)->count(),
                'vencidos' => $abertos()->whereNotNull('protocols.prazo_atendimento')->whereDate('protocols.prazo_atendimento', '<', $hoje)->count(),
                'vence_em_breve' => $abertos()->whereNotNull('protocols.prazo_atendimento')
                    ->whereDate('protocols.prazo_atendimento', '>=', $hoje)->whereDate('protocols.prazo_atendimento', '<=', now()->addDays(3)->toDateString())->count(),
            ],
            'tempo_medio_recebimento_horas' => round(max(0, $horasRecebimento), 1),
            'tempo_medio_conclusao_horas' => round(max(0, $horasConclusao), 1),
            'taxa_no_prazo' => $totalComPrazo > 0 ? round($noPrazo * 100 / $totalComPrazo, 1) : null,
            'por_status' => $porStatus->map(fn ($total, $status) => ['status' => $status, 'total' => (int) $total])->values(),
            'por_prioridade' => $periodo()->select(DB::raw("COALESCE(protocols.prioridade, 'normal') as prioridade"), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw("COALESCE(protocols.prioridade, 'normal')"))->orderByDesc('total')->get(),
            'por_tipo' => $periodo()->leftJoin('protocol_types as t', 't.codigo', '=', 'protocols.tipo')
                ->select(DB::raw('COALESCE(t.nome, protocols.tipo) as nome'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('COALESCE(t.nome, protocols.tipo)'))->orderByDesc('total')->limit(8)->get(),
            'por_secretaria' => $ranking($abertos()->join('secretarias as s', 's.id', '=', 'protocols.secretaria_id'), 's.nome', 's.id'),
            'por_unidade' => $ranking($abertos()->join('protocol_organizational_units as u', 'u.id', '=', 'protocols.destino_unit_id'), 'u.nome', 'u.id'),
            'por_responsavel' => $ranking($abertos()->join('users as r', 'r.id', '=', 'protocols.responsavel_atual_id'), 'r.name', 'r.id'),
            'por_dia' => $periodo()->select(DB::raw('DATE(protocols.created_at) as dia'), DB::raw('COUNT(*) as abertos'))
                ->groupBy('dia')->orderBy('dia')->get(),
            // Resolvidos contam no dia da conclusão (não no da abertura), dentro do período.
            'resolvidos_por_dia' => $todos()->whereIn('protocols.status', ['encerrado', 'concluido'])
                ->whereRaw("{$fim} >= ?", [$desde])
                ->select(DB::raw("DATE({$fim}) as dia"), DB::raw('COUNT(*) as resolvidos'))
                ->groupBy('dia')->orderBy('dia')->get(),
        ];

        return response()->json($data)->header('Cache-Control', 'private, max-age=60');
    }
}
