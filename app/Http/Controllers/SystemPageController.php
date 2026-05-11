<?php

namespace App\Http\Controllers;

use App\Models\SystemPage;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SystemPageController extends Controller
{
    public function index()
    {
        return response()->json(
            SystemPage::orderBy('categoria')->orderBy('titulo')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'    => 'required|string|max:80',
            'path'      => 'required|string|max:120|unique:system_pages,path',
            'icone'     => 'nullable|string|max:40',
            'categoria' => 'nullable|string|max:60',
        ]);

        $page = SystemPage::create($request->only(['titulo', 'path', 'icone', 'categoria']));
        AuditService::record('CREATE', $page, null, $page->toArray());
        return response()->json($page, 201);
    }

    public function update(Request $request, $id)
    {
        $page = SystemPage::find($id);
        if (!$page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }

        $request->validate([
            'titulo'    => 'sometimes|string|max:80',
            'path'      => 'sometimes|string|max:120|unique:system_pages,path,' . $id,
            'icone'     => 'nullable|string|max:40',
            'categoria' => 'nullable|string|max:60',
            'ativo'     => 'sometimes|boolean',
        ]);

        $old = $page->toArray();
        $page->update($request->only(['titulo', 'path', 'icone', 'categoria', 'ativo']));
        AuditService::record('UPDATE', $page, $old, $page->toArray());
        return response()->json($page);
    }

    public function destroy($id)
    {
        $page = SystemPage::find($id);
        if (!$page) {
            return response()->json(['error' => 'Página não encontrada'], 404);
        }
        AuditService::record('DELETE', $page, $page->toArray(), null);
        $page->delete();
        return response()->json(['message' => 'Página removida.']);
    }
}
