<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

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
        $totais = $this->service->getFilaTotais();

        return response()->json([
            'total_na_fila'          => $totais['total_na_fila'],
            'fila_7_dias'            => $totais['fila_7_dias'],
            'total_realizados'       => $totais['total_realizados'],
            'fila_por_especialidade' => $this->service->getFilaPorEspecialidade(),
            'fila_por_mes'           => $this->service->getFilaPorMes(),
        ]);
    }

    public function tfd()
    {
        $totais = $this->service->getTfdTotais();

        return response()->json([
            'total_viagens_mes'         => $totais['total_viagens_mes'],
            'pessoas_transportadas_mes' => $totais['pessoas_transportadas_mes'],
            'km_rodados_mes'            => $totais['km_rodados_mes'],
            'viagens_por_dia'           => $this->service->getViagensPorDia(),
            'viagens_por_motorista'     => $this->service->getViagensPorMotorista(),
            'top_rotas'                 => $this->service->getTopRotas(),
        ]);
    }

    public function logs()
    {
        $totais = $this->service->getLogsTotais();

        return response()->json([
            'total_qr'           => $totais['total_qr'],
            'total_link_publico' => $totais['total_link_publico'],
            'qr_mes'             => $totais['qr_mes'],
            'link_publico_mes'   => $totais['link_publico_mes'],
            'qr_por_dia'         => $this->service->getQrPorDia(),
            'link_por_dia'       => $this->service->getLinkPorDia(),
        ]);
    }
}
