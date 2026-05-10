<?php

namespace App\Http\Controllers;

use App\Services\Laboratorio\ResultadoExameService;
use Illuminate\Http\Request;

class ConsultaPublicaController extends Controller
{
    public function __construct(private ResultadoExameService $service) {}

    public function consultar(Request $request)
    {
        $request->validate([
            'protocolo' => 'required|string',
            'senha'     => 'required|string',
        ]);

        $resultado = $this->service->consultarPublico(
            $request->input('protocolo'),
            $request->input('senha')
        );

        if (!$resultado) {
            return response()->json([
                'error' => 'Protocolo ou senha inválidos, ou resultado expirado.',
            ], 404);
        }

        $campos = $resultado->campos->groupBy('exame_id')->map(function ($itens) {
            $exame = $itens->first()->campo->exame ?? null;
            return [
                'nome_exame' => $exame?->nome ?? 'Exame',
                'campos'     => $itens->map(fn($rc) => [
                    'campo'             => $rc->campo->nome ?? '—',
                    'unidade'           => $rc->campo->unidade ?? null,
                    'valor_numerico'    => $rc->valor_numerico,
                    'valor_texto'       => $rc->valor_texto,
                    'status_referencia' => $rc->status_referencia,
                    'observacao'        => $rc->observacao,
                ])->values(),
            ];
        });

        return response()->json([
            'protocolo'       => $resultado->protocolo,
            'data_liberacao'  => $resultado->data_liberacao,
            'data_validade'   => $resultado->data_validade,
            'paciente'        => [
                'nome'      => $this->maskName($resultado->pedido->cliente->name ?? ''),
                'born_date' => $resultado->pedido->cliente->born_date ?? null,
            ],
            'medico_solicitante' => $resultado->pedido->medico_solicitante,
            'campos_por_exame'   => $campos,
        ]);
    }

    // Exibe as 2 primeiras letras de cada palavra e mascara o restante
    // "Douglas Lundy" → "Do**** Lu***"
    private function maskName(string $name): string
    {
        if (empty($name)) return '—';

        return collect(explode(' ', $name))
            ->map(fn($word) => strlen($word) <= 2
                ? $word
                : substr($word, 0, 2) . str_repeat('*', strlen($word) - 2))
            ->implode(' ');
    }
}
