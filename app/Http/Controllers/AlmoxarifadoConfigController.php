<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmoxarifadoConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(AlmoxarifadoConfig::current());
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'permitir_saida_sem_saldo' => 'boolean',
            'permitir_transferencia_entre_secretarias' => 'boolean',
            'exigir_justificativa_saida' => 'boolean',
            'exigir_localizacao_produto' => 'boolean',
            'notificar_estoque_minimo' => 'boolean',
            'estoque_minimo_alerta_percentual' => 'nullable|integer|min:1|max:100',
            'permite_produto_sem_validade' => 'boolean',
            'observacoes' => 'nullable|string',
        ]);

        $config = AlmoxarifadoConfig::current();
        $config->update($request->only([
            'permitir_saida_sem_saldo',
            'permitir_transferencia_entre_secretarias',
            'exigir_justificativa_saida',
            'exigir_localizacao_produto',
            'notificar_estoque_minimo',
            'estoque_minimo_alerta_percentual',
            'permite_produto_sem_validade',
            'observacoes',
        ]));

        return response()->json($config->fresh());
    }
}
