<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaExameRequest;
use App\Models\CategoriaExame;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CategoriaExameController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriaExame::query();

        if ($request->has('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('busca')) {
            $query->where('nome', 'like', '%' . $request->busca . '%');
        }

        if ($request->boolean('all')) {
            return response()->json($query->where('ativo', true)->orderBy('nome')->get());
        }

        return response()->json($query->orderBy('nome')->paginate($request->input('per_page', 30)));
    }

    public function store(StoreCategoriaExameRequest $request)
    {
        $categoria = CategoriaExame::create([
            'nome'  => strtoupper($request->input('nome')),
            'ativo' => $request->input('ativo', true),
        ]);
        AuditService::record('CREATE', $categoria, null, $categoria->toArray());

        return response()->json([
            'message'   => 'Categoria criada com sucesso!',
            'categoria' => $categoria,
        ], 201);
    }

    public function update(StoreCategoriaExameRequest $request, $id)
    {
        $categoria = CategoriaExame::find($id);
        if (!$categoria) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        $old = $categoria->toArray();
        $categoria->nome  = strtoupper($request->input('nome'));
        $categoria->ativo = $request->input('ativo', $categoria->ativo);
        $categoria->save();
        AuditService::record('UPDATE', $categoria, $old, $categoria->toArray());

        return response()->json([
            'message'   => 'Categoria atualizada com sucesso!',
            'categoria' => $categoria,
        ]);
    }

    public function destroy($id)
    {
        $categoria = CategoriaExame::find($id);
        if (!$categoria) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        AuditService::record('DELETE', $categoria, $categoria->toArray(), null);
        $categoria->delete();

        return response()->json(['message' => 'Categoria removida com sucesso!']);
    }
}
