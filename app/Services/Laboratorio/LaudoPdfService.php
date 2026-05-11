<?php

namespace App\Services\Laboratorio;

use App\Models\LabConfig;
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
            'campos.campo.referencias',
            'campos.campo.exame',
            'liberadoPor',
        ]);

        $camposPorExame = $resultado->campos->groupBy('exame_id');

        $config = LabConfig::get();

        $brasaoPath  = public_path('files/brasao.png');
        $logoSusPath = public_path('files/logosus.png');

        $brasaoB64  = file_exists($brasaoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($brasaoPath))
            : null;

        $logoSusB64 = file_exists($logoSusPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoSusPath))
            : null;

        $pdf = Pdf::loadView('pdf.laudo', [
            'resultado'      => $resultado,
            'camposPorExame' => $camposPorExame,
            'config'         => $config,
            'brasaoB64'      => $brasaoB64,
            'logoSusB64'     => $logoSusB64,
        ])->setPaper('a4', 'portrait');

        $path = 'lab/resultados/' . $resultado->protocolo . '.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }
}
