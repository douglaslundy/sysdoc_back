<?php

namespace App\Http\Controllers;

use App\Models\PageCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            PageCategory::orderBy('ordem')->orderBy('nome')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:60|unique:page_categories,nome',
            'icone' => 'nullable|string|max:40',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ]);

        $category = DB::transaction(function () use ($request) {
            $targetOrder = $request->filled('ordem')
                ? (int) $request->ordem
                : ((int) PageCategory::max('ordem') + 1);

            // Insere sem conflito: desloca para baixo todas as categorias
            // que já ocupam a posição informada ou abaixo.
            PageCategory::where('ordem', '>=', $targetOrder)->increment('ordem');

            return PageCategory::create([
                'nome' => $request->nome,
                'icone' => $request->icone,
                'ordem' => $targetOrder,
                'ativo' => $request->input('ativo', true),
            ]);
        });

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = PageCategory::find($id);
        if (! $category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        $request->validate([
            'nome' => 'sometimes|string|max:60|unique:page_categories,nome,'.$id,
            'icone' => 'nullable|string|max:40',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $category) {
            $payload = $request->only(['nome', 'icone', 'ativo']);

            if ($request->filled('ordem')) {
                $oldOrder = (int) $category->ordem;
                $newOrder = (int) $request->ordem;

                if ($newOrder < $oldOrder) {
                    // Moveu para cima: empurra para baixo o intervalo atingido.
                    PageCategory::where('id', '!=', $category->id)
                        ->where('ordem', '>=', $newOrder)
                        ->where('ordem', '<', $oldOrder)
                        ->increment('ordem');
                } elseif ($newOrder > $oldOrder) {
                    // Moveu para baixo: puxa para cima o intervalo intermediário.
                    PageCategory::where('id', '!=', $category->id)
                        ->where('ordem', '>', $oldOrder)
                        ->where('ordem', '<=', $newOrder)
                        ->decrement('ordem');
                }

                $payload['ordem'] = $newOrder;
            }

            $category->update($payload);
        });

        $category->refresh();

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = PageCategory::find($id);
        if (! $category) {
            return response()->json(['error' => 'Categoria não encontrada'], 404);
        }

        if ($category->pages()->exists()) {
            return response()->json(['error' => 'Categoria em uso por páginas do sistema'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Categoria removida.']);
    }
}
