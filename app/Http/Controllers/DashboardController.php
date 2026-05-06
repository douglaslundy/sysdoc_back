<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function laboratorio()
    {
        return response()->json([
            'totais'                => $this->service->getTotais(),
            'pedidos_por_status'    => $this->service->getPedidosPorStatus(),
            'pedidos_por_mes'       => $this->service->getPedidosPorMes(),
            'top_exames'            => $this->service->getTopExames(),
            'pedidos_por_categoria' => $this->service->getPedidosPorCategoria(),
            'clientes_por_mes'      => $this->service->getClientesPorMes(),
            'resultados_status'     => $this->service->getResultadosStatus(),
            'top_medicos'           => $this->service->getTopMedicos(),
        ]);
    }

    public function fila()
    {
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

        return response()->json([
            'totais' => [
                'total_fila'       => $totais['total_na_fila'],
                'fila_7dias'       => $totais['fila_7_dias'],
                'total_realizados' => $totais['total_realizados'],
            ],
            'especialidades'   => $especialidades,
            'entradas_por_mes' => $entradasPorMes,
        ]);
    }

    public function tfd()
    {
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

        return response()->json([
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
        ]);
    }

    public function logs()
    {
        $totais = $this->service->getLogsTotais();

        return response()->json([
            'totais' => [
                'total_qr'   => $totais['total_qr'],
                'total_link' => $totais['total_link_publico'],
                'qr_mes'     => $totais['qr_mes'],
                'link_mes'   => $totais['link_publico_mes'],
            ],
            'qr_por_dia'   => $this->service->getQrPorDia(),
            'link_por_dia' => $this->service->getLinkPorDia(),
        ]);
    }
}
