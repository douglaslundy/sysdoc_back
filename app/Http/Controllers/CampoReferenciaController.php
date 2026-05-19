<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampoReferenciaRequest;
use App\Models\CampoReferencia;
use App\Models\ExameCampo;
use App\Services\AuditService;

class CampoReferenciaController extends Controller
{
    public function index($campoId)
    {
        $campo = ExameCampo::find($campoId);
        if (! $campo) {
            return response()->json(['error' => 'Campo não encontrado'], 404);
        }

        $referencias = CampoReferencia::where('exame_campo_id', $campoId)->get();

        return response()->json($referencias);
    }

    public function store(StoreCampoReferenciaRequest $request, $campoId)
    {
        $campo = ExameCampo::find($campoId);
        if (! $campo) {
            return response()->json(['error' => 'Campo não encontrado'], 404);
        }

        $referencia = new CampoReferencia;
        $referencia->exame_campo_id = $campoId;
        $referencia->perfil = $request->input('perfil');
        $referencia->valor_min = $request->input('valor_min');
        $referencia->valor_max = $request->input('valor_max');
        $referencia->valor_texto = $request->input('valor_texto');
        $referencia->descricao = $request->input('descricao');
        $referencia->save();
        AuditService::record('CREATE', $referencia, null, $referencia->toArray());

        return response()->json([
            'message' => 'Referência criada com sucesso!',
            'referencia' => $referencia,
        ], 201);
    }

    public function update(StoreCampoReferenciaRequest $request, $campoId, $referenciaId)
    {
        $referencia = CampoReferencia::where('exame_campo_id', $campoId)->find($referenciaId);
        if (! $referencia) {
            return response()->json(['error' => 'Referência não encontrada'], 404);
        }

        $old = $referencia->toArray();
        $referencia->perfil = $request->input('perfil');
        $referencia->valor_min = $request->input('valor_min');
        $referencia->valor_max = $request->input('valor_max');
        $referencia->valor_texto = $request->input('valor_texto');
        $referencia->descricao = $request->input('descricao');
        $referencia->save();
        AuditService::record('UPDATE', $referencia, $old, $referencia->toArray());

        return response()->json([
            'message' => 'Referência atualizada com sucesso!',
            'referencia' => $referencia,
        ]);
    }

    public function destroy($campoId, $referenciaId)
    {
        $referencia = CampoReferencia::where('exame_campo_id', $campoId)->find($referenciaId);
        if (! $referencia) {
            return response()->json(['error' => 'Referência não encontrada'], 404);
        }
        AuditService::record('DELETE', $referencia, $referencia->toArray(), null);
        $referencia->delete();

        return response()->json(['message' => 'Referência removida com sucesso!']);
    }
}
