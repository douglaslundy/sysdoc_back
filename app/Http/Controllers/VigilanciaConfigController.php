<?php

namespace App\Http\Controllers;

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

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'estado' => 'nullable|string|max:2',
            'nome_municipio' => 'nullable|string|max:255',
            'nome_prefeitura' => 'nullable|string|max:255',
            'cnpj_prefeitura' => 'nullable|string|max:14',
            'nome_secretaria' => 'nullable|string|max:255',
            'cnpj_secretaria' => 'nullable|string|max:14',
            'divisao' => 'nullable|string|max:255',
            'endereco' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:8',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nome_responsavel' => 'nullable|string|max:255',
            'cargo_responsavel' => 'nullable|string|max:255',
            'grant_type' => 'nullable|string|max:255',
            'observacoes' => 'nullable|array',
            'observacoes.*' => 'string|max:500',
        ]);

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
