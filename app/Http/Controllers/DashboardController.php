<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function laboratorio()
    {
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

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function fila()
    {
        $data = Cache::remember('dashboard.fila', 120, function () {
            try {
                $totais = $this->service->getFilaTotais();
            } catch (\Throwable $e) {
                Log::error('DashboardFila totais: ' . $e->getMessage());
                $totais = ['total_na_fila' => 0, 'fila_7_dias' => 0, 'total_realizados' => 0];
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
                    'total_fila'       => $totais['total_na_fila'],
                    'fila_7dias'       => $totais['fila_7_dias'],
                    'total_realizados' => $totais['total_realizados'],
                ],
                'especialidades'           => $especialidades,
                'entradas_por_mes'         => $entradasPorMes,
                'especialidades_realizadas' => $especialidadesRealizadas,
                'realizadas_por_mes'        => $realizadasPorMes,
            ];
        });

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function tfd()
    {
        $periodo = in_array(request()->input('periodo'), ['mes', '12meses', 'ano'])
            ? request()->input('periodo')
            : 'mes';

        $cacheKey = 'dashboard.tfd.' . $periodo . '.' . now()->format('Y-m');
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

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function logs()
    {
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

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
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
        $data = Cache::remember('dashboard.vigilancia', 300, function () {
            try {
                $totais = $this->service->getVigilanciaTotais();
            } catch (\Throwable $e) {
                Log::error('DashboardVigilancia totais: ' . $e->getMessage());
                $totais = ['estabelecimentos' => 0, 'alvaras' => 0, 'vigentes' => 0, 'vencidos' => 0, 'a_vencer' => 0, 'vencendo_em_30' => 0];
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
                'totais'              => $totais,
                'por_status'          => $porStatus,
                'por_nivel_risco'     => $porNivelRisco,
                'por_mes'             => $porMes,
                'proximos_vencimentos' => $proximosVencimentos,
            ];
        });

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }
}
