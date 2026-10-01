<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVigilanciaConfigRequest;
use App\Models\VigilanciaConfig;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VigilanciaConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(VigilanciaConfig::get());
    }

    public function update(UpdateVigilanciaConfigRequest $request): JsonResponse
    {
        $config = VigilanciaConfig::get();
        $old = $config->toArray();

        $config->update($request->only([
            'estado', 'nome_municipio', 'nome_prefeitura', 'cnpj_prefeitura',
            'nome_secretaria', 'cnpj_secretaria', 'divisao',
            'endereco', 'cep', 'telefone', 'email',
            'nome_responsavel', 'cargo_responsavel', 'grant_type', 'observacoes',
        ]));

        AuditService::record('UPDATE', $config, $old, $config->fresh()->toArray());

        return response()->json($config->fresh());
    }
}
