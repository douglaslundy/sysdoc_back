<?php

namespace App\Services\Almoxarifado;

use App\Models\AlmoxarifadoRequisicao;
use Barryvdh\DomPDF\Facade\Pdf;

class RequisicaoPdfService
{
    public function download(AlmoxarifadoRequisicao $requisicao)
    {
        $requisicao->loadMissing([
            'secretaria:id,nome,sigla',
            'requisitante:id,name,email',
            'responsavel:id,name',
            'itens.produto:id,nome,codigo_interno',
            'historicos.user:id,name',
        ]);

        return Pdf::loadView('pdf.almoxarifado-requisicao', compact('requisicao'))
            ->setPaper('a4')
            ->download("requisicao-{$requisicao->numero}.pdf");
    }
}
