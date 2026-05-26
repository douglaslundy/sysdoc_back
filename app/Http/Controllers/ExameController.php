<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExameRequest;
use App\Models\Exame;
use Illuminate\Http\Request;

class ExameController extends Controller
{
    public function index(Request $request)
    {
        $query = Exame::with(['camposAtivos', 'categoriaExame']);

        if ($request->has('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('categoria_exame_id')) {
            $query->where('categoria_exame_id', $request->categoria_exame_id);
        }

        $exames = $query->orderBy('nome')->paginate($request->input('per_page', 20));

        return response()->json($exames);
    }

    public function store(StoreExameRequest $request)
    {
        $exame = Exame::create([
            'nome' => strtoupper($request->input('nome')),
            'codigo' => strtoupper($request->input('codigo')),
            'categoria_exame_id' => $request->input('categoria_exame_id'),
            'descricao' => $request->input('descricao'),
            'ativo' => $request->input('ativo', true),
        ]);

        $exame->load('categoriaExame');

        return response()->json([
            'message' => 'Exame criado com sucesso!',
            'exame' => $exame,
        ], 201);
    }

    public function show($id)
    {
        $exame = Exame::with(['campos.referencias', 'categoriaExame'])->find($id);
        if (! $exame) {
            return response()->json(['error' => 'Exame não encontrado'], 404);
        }

        return response()->json($exame);
    }

    public function update(StoreExameRequest $request, $id)
    {
        $exame = Exame::find($id);
        if (! $exame) {
            return response()->json(['error' => 'Exame não encontrado'], 404);
        }

        $exame->nome = strtoupper($request->input('nome'));
        $exame->codigo = strtoupper($request->input('codigo'));
        $exame->categoria_exame_id = $request->input('categoria_exame_id');
        $exame->descricao = $request->input('descricao');
        $exame->ativo = $request->input('ativo', $exame->ativo);
        $exame->save();

        $exame->load('categoriaExame');

        return response()->json([
            'message' => 'Exame atualizado com sucesso!',
            'exame' => $exame,
        ]);
    }

    public function destroy($id)
    {
        $exame = Exame::find($id);
        if (! $exame) {
            return response()->json(['error' => 'Exame não encontrado'], 404);
        }
        $exame->delete();

        return response()->json(['message' => 'Exame removido com sucesso!']);
    }
}
