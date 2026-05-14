<?php

namespace App\Services;

use App\Models\Alvara;
use App\Models\VigilanciaConfig;
use Barryvdh\DomPDF\Facade\Pdf;

class AlvaraPdfService
{
    public function gerar(Alvara $alvara): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $alvara->load('estabelecimento');

        $config = VigilanciaConfig::get();

        $brasaoPath = public_path('files/brasao.png');
        $brasaoB64  = file_exists($brasaoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($brasaoPath))
            : null;

        $pdf = Pdf::loadView('pdf.alvara', [
            'alvara'    => $alvara,
            'config'    => $config,
            'brasaoB64' => $brasaoB64,
        ])->setPaper('a4', 'portrait');

        $safeNumber = preg_replace('/[\\\\\\/]+/', '-', (string) $alvara->numero_alvara);
        $filename = 'alvara-' . ($safeNumber ?: 'sem-numero') . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
