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

            return [
                'totais'                => $totais,
                'pedidos_por_status'    => $pedidosPorStatus,
                'pedidos_por_mes'       => $pedidosPorMes,
                'top_exames'            => $topExames,
                'pedidos_por_categoria' => $pedidosPorCategoria,
                'clientes_por_mes'      => $clientesPorMes,
                'resultados_status'     => $resultadosStatus,
                'top_medicos'           => $topMedicos,
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

            return [
                'totais' => [
                    'total_fila'       => $totais['total_na_fila'],
                    'fila_7dias'       => $totais['fila_7_dias'],
                    'total_realizados' => $totais['total_realizados'],
                ],
                'especialidades'   => $especialidades,
                'entradas_por_mes' => $entradasPorMes,
            ];
        });

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }

    public function tfd()
    {
        $data = Cache::remember('dashboard.tfd', 300, function () {
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
                $motoristas = $this->service->getViagensPorMotorista();
            } catch (\Throwable $e) {
                Log::error('DashboardTfd motoristas: ' . $e->getMessage());
                $motoristas = collect();
            }

            try {
                $rotas = $this->service->getTopRotas();
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
                'viagens_por_dia' => $viagensPorDia,
                'motoristas'      => $motoristas,
                'rotas'           => $rotas,
                'viagens_por_mes' => $viagensPorMes,
                'viagens_por_ano' => $viagensPorAno,
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
}
