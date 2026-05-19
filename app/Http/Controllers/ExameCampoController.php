<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExameCampoRequest;
use App\Models\Exame;
use App\Models\ExameCampo;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExameCampoController extends Controller
{
    public function index($exameId)
    {
        $exame = Exame::find($exameId);
        if (! $exame) {
            return response()->json(['error' => 'Exame não encontrado'], 404);
        }

        $campos = ExameCampo::with(['referencias'])
            ->where('exame_id', $exameId)
            ->orderBy('ordem')
            ->get();

        return response()->json($campos);
    }

    public function store(StoreExameCampoRequest $request, $exameId)
    {
        $exame = Exame::find($exameId);
        if (! $exame) {
            return response()->json(['error' => 'Exame não encontrado'], 404);
        }

        $campo = new ExameCampo;
        $campo->exame_id = $exameId;
        $campo->nome = $request->input('nome');
        $campo->descricao = $request->input('descricao');
        $campo->tipo_valor = $request->input('tipo_valor', 'numerico');
        $campo->unidade = $request->input('unidade');
        $campo->opcoes_selecao = $request->input('opcoes_selecao');
        $campo->ordem = $request->input('ordem', 0);
        $campo->obrigatorio = $request->input('obrigatorio', true);
        $campo->ativo = $request->input('ativo', true);
        $campo->save();
        AuditService::record('CREATE', $campo, null, $campo->toArray());

        return response()->json([
            'message' => 'Campo criado com sucesso!',
            'campo' => $campo,
        ], 201);
    }

    public function update(StoreExameCampoRequest $request, $exameId, $campoId)
    {
        $campo = ExameCampo::where('exame_id', $exameId)->find($campoId);
        if (! $campo) {
            return response()->json(['error' => 'Campo não encontrado'], 404);
        }

        $old = $campo->toArray();
        $campo->nome = $request->input('nome');
        $campo->descricao = $request->input('descricao');
        $campo->tipo_valor = $request->input('tipo_valor', $campo->tipo_valor);
        $campo->unidade = $request->input('unidade');
        $campo->opcoes_selecao = $request->input('opcoes_selecao');
        $campo->obrigatorio = $request->input('obrigatorio', $campo->obrigatorio);
        $campo->ativo = $request->input('ativo', $campo->ativo);
        $campo->save();
        AuditService::record('UPDATE', $campo, $old, $campo->toArray());

        return response()->json([
            'message' => 'Campo atualizado com sucesso!',
            'campo' => $campo,
        ]);
    }

    public function destroy($exameId, $campoId)
    {
        $campo = ExameCampo::where('exame_id', $exameId)->find($campoId);
        if (! $campo) {
            return response()->json(['error' => 'Campo não encontrado'], 404);
        }
        AuditService::record('DELETE', $campo, $campo->toArray(), null);
        $campo->delete();

        return response()->json(['message' => 'Campo removido com sucesso!']);
    }

    public function reordenar(Request $request, $exameId)
    {
        $request->validate([
            'ordem' => 'required|array',
            'ordem.*' => 'integer|exists:exame_campos,id',
        ]);

        foreach ($request->ordem as $posicao => $campoId) {
            ExameCampo::where('id', $campoId)
                ->where('exame_id', $exameId)
                ->update(['ordem' => $posicao]);
        }

        return response()->json(['message' => 'Ordem atualizada com sucesso!']);
    }
}
