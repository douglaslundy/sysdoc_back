<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAlmoxarifadoConfigRequest;
use App\Models\AlmoxarifadoConfig;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmoxarifadoConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        // Admin em "Todas" escolhe a secretaria (?secretaria_id=); os demais usam a ativa.
        return response()->json(AlmoxarifadoConfig::current(EscopoAlmoxarifado::id($request->input('secretaria_id'))));
    }

    public function update(UpdateAlmoxarifadoConfigRequest $request): JsonResponse
    {
        $config = AlmoxarifadoConfig::current(EscopoAlmoxarifado::id($request->input('secretaria_id')));
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
