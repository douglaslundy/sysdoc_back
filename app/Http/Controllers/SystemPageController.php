<?php

namespace App\Http\Controllers;

use App\Models\PageCategory;
use App\Models\SystemPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemPageController extends Controller
{
    public function index()
    {
        return response()->json(
            SystemPage::with('category:id,nome,icone,ordem,ativo')
                ->orderBy('categoria')
                ->orderBy('ordem')
                ->orderBy('titulo')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:80',
            'path' => 'required|string|max:120|unique:system_pages,path',
            'icone' => 'nullable|string|max:40',
            'categoria' => 'nullable|string|max:60',
            'category_id' => 'nullable|integer|exists:page_categories,id',
            'ordem' => 'nullable|integer|min:1',
        ]);

        $page = DB::transaction(function () use ($request) {
            $data = $request->only(['titulo', 'path', 'icone', 'categoria', 'category_id']);
            $targetCategoryId = $data['category_id'] ?? null;

            if (! empty($targetCategoryId)) {
                $category = PageCategory::find($targetCategoryId);
                if ($category) {
                    $data['categoria'] = $category->nome;
                }
            }

            $maxOrderInGroup = SystemPage::query()
                ->when($targetCategoryId, fn ($q) => $q->where('category_id', $targetCategoryId), fn ($q) => $q->whereNull('category_id')->where('categoria', $data['categoria'] ?? null))
                ->max('ordem');

            $targetOrder = $request->filled('ordem')
                ? (int) $request->ordem
                : ((int) $maxOrderInGroup + 1);

            SystemPage::query()
                ->when($targetCategoryId, fn ($q) => $q->where('category_id', $targetCategoryId), fn ($q) => $q->whereNull('category_id')->where('categoria', $data['categoria'] ?? null))
                ->where('ordem', '>=', $targetOrder)
                ->increment('ordem');

            $data['ordem'] = $targetOrder;

            return SystemPage::create($data);
        });

        return response()->json($page->load('category:id,nome,icone,ordem,ativo'), 201);
    }

    public function update(Request $request, $id)
    {
        $page = SystemPage::find($id);
        if (! $page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        $request->validate([
            'titulo' => 'sometimes|string|max:80',
            'path' => 'sometimes|string|max:120|unique:system_pages,path,'.$id,
            'icone' => 'nullable|string|max:40',
            'categoria' => 'nullable|string|max:60',
            'category_id' => 'nullable|integer|exists:page_categories,id',
            'ordem' => 'nullable|integer|min:1',
            'ativo' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($request, $page) {
            $data = $request->only(['titulo', 'path', 'icone', 'categoria', 'category_id', 'ativo']);

            $newCategoryId = array_key_exists('category_id', $data) ? $data['category_id'] : $page->category_id;
            if (! empty($newCategoryId)) {
                $category = PageCategory::find($newCategoryId);
                if ($category) {
                    $data['categoria'] = $category->nome;
                }
            }

            $oldCategoryId = $page->category_id;
            $oldCategoria = $page->categoria;
            $categoryChanged = $newCategoryId != $oldCategoryId || (($data['categoria'] ?? $page->categoria) !== $oldCategoria);

            $newOrder = $request->filled('ordem') ? (int) $request->ordem : (int) $page->ordem;

            if ($categoryChanged) {
                SystemPage::query()
                    ->where('id', '!=', $page->id)
                    ->when($oldCategoryId, fn ($q) => $q->where('category_id', $oldCategoryId), fn ($q) => $q->whereNull('category_id')->where('categoria', $oldCategoria))
                    ->where('ordem', '>', $page->ordem)
                    ->decrement('ordem');

                SystemPage::query()
                    ->where('id', '!=', $page->id)
                    ->when($newCategoryId, fn ($q) => $q->where('category_id', $newCategoryId), fn ($q) => $q->whereNull('category_id')->where('categoria', $data['categoria'] ?? null))
                    ->where('ordem', '>=', $newOrder)
                    ->increment('ordem');

                $data['ordem'] = $newOrder;
            } elseif ($request->filled('ordem') && $newOrder !== (int) $page->ordem) {
                if ($newOrder < (int) $page->ordem) {
                    SystemPage::query()
                        ->where('id', '!=', $page->id)
                        ->when($page->category_id, fn ($q) => $q->where('category_id', $page->category_id), fn ($q) => $q->whereNull('category_id')->where('categoria', $page->categoria))
                        ->where('ordem', '>=', $newOrder)
                        ->where('ordem', '<', $page->ordem)
                        ->increment('ordem');
                } else {
                    SystemPage::query()
                        ->where('id', '!=', $page->id)
                        ->when($page->category_id, fn ($q) => $q->where('category_id', $page->category_id), fn ($q) => $q->whereNull('category_id')->where('categoria', $page->categoria))
                        ->where('ordem', '>', $page->ordem)
                        ->where('ordem', '<=', $newOrder)
                        ->decrement('ordem');
                }
                $data['ordem'] = $newOrder;
            }

            $page->update($data);
        });

        $page->refresh();

        return response()->json($page->load('category:id,nome,icone,ordem,ativo'));
    }

    public function destroy($id)
    {
        $page = SystemPage::find($id);
        if (! $page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        DB::transaction(function () use ($page) {
            SystemPage::query()
                ->where('id', '!=', $page->id)
                ->when($page->category_id, fn ($q) => $q->where('category_id', $page->category_id), fn ($q) => $q->whereNull('category_id')->where('categoria', $page->categoria))
                ->where('ordem', '>', $page->ordem)
                ->decrement('ordem');

            $page->delete();
        });

        return response()->json(['message' => 'Página removida.']);
    }
}
