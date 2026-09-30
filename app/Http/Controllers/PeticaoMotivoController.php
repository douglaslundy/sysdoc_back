<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeticaoMotivoRequest;
use App\Models\PeticaoMotivo;
use Illuminate\Http\JsonResponse;

class PeticaoMotivoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            PeticaoMotivo::with('unit:id,nome,tipo')->orderBy('ordem')->orderBy('nome')->get()
        );
    }

    public function store(PeticaoMotivoRequest $request): JsonResponse
    {
        $motivo = PeticaoMotivo::create($request->validated());

        return response()->json($motivo->fresh()->load('unit:id,nome,tipo'), 201);
    }

    public function update(PeticaoMotivoRequest $request, PeticaoMotivo $motivo): JsonResponse
    {
        $motivo->update($request->validated());

        return response()->json($motivo->fresh()->load('unit:id,nome,tipo'));
    }

    public function destroy(PeticaoMotivo $motivo): JsonResponse
    {
        $motivo->delete();

        return response()->json(['message' => 'Motivo excluído com sucesso']);
    }

    /** Lista pública (formulário /petition): só motivos ativos, sem expor a unidade responsável. */
    public function publicIndex(): JsonResponse
    {
        return response()->json(
            PeticaoMotivo::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get(['id', 'nome', 'descricao'])
        );
    }
}
