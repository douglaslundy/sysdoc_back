<?php

namespace App\Services\Laboratorio;

use App\Models\ResultadoExame;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class LaudoPdfService
{
    public function gerar(ResultadoExame $resultado): string
    {
        $resultado->load([
            'pedido.cliente',
            'pedido.medicoSolicitante',
            'campos.campo',
            'liberadoPor',
        ]);

        $camposPorExame = $resultado->campos->groupBy('exame_id');

        $pdf = Pdf::loadView('pdf.laudo', [
            'resultado'      => $resultado,
            'camposPorExame' => $camposPorExame,
        ])->setPaper('a4', 'portrait');

        $path = 'lab/resultados/' . $resultado->protocolo . '.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }
}
