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
}
