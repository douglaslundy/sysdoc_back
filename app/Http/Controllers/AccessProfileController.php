<?php

namespace App\Http\Controllers;

use App\Models\AccessProfile;
use Illuminate\Http\Request;

class AccessProfileController extends Controller
{
    public function index()
    {
        return response()->json(
            AccessProfile::with('pages')->orderBy('nome')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:60|unique:access_profiles,nome',
            'slug' => 'required|string|max:60|unique:access_profiles,slug|alpha_dash',
            'descricao' => 'nullable|string|max:200',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ]);

        $profile = AccessProfile::create([
            'nome' => $request->nome,
            'slug' => $request->slug,
            'descricao' => $request->descricao,
            'ativo' => true,
        ]);

        if ($request->has('page_ids')) {
            $profile->pages()->sync($request->page_ids);
        }

        return response()->json($profile->load('pages'), 201);
    }

    public function show($id)
    {
        $profile = AccessProfile::with('pages')->find($id);
        if (! $profile) {
            return response()->json(['error' => 'Perfil não encontrado'], 404);
        }

        return response()->json($profile);
    }

    public function update(Request $request, $id)
    {
        $profile = AccessProfile::find($id);
        if (! $profile) {
            return response()->json(['error' => 'Perfil não encontrado'], 404);
        }

        $request->validate([
            'nome' => 'sometimes|string|max:60|unique:access_profiles,nome,'.$id,
            'slug' => 'sometimes|string|max:60|unique:access_profiles,slug,'.$id.'|alpha_dash',
            'descricao' => 'nullable|string|max:200',
            'ativo' => 'sometimes|boolean',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ]);

        $profile->update($request->only(['nome', 'slug', 'descricao', 'ativo']));

        if ($request->has('page_ids')) {
            $profile->pages()->sync($request->page_ids);
        }

        return response()->json($profile->load('pages'));
    }

    public function destroy($id)
    {
        $profile = AccessProfile::find($id);
        if (! $profile) {
            return response()->json(['error' => 'Perfil não encontrado'], 404);
        }
        $profile->delete();

        return response()->json(['message' => 'Perfil removido.']);
    }

    // Retorna páginas permitidas para o perfil do usuário logado
    public function myPermissions(Request $request)
    {
        $user = $request->user();
        $profile = AccessProfile::where('slug', $user->profile)
            ->where('ativo', true)
            ->with([
                'pages' => function ($query) {
                    $query->select('system_pages.id', 'titulo', 'path', 'icone', 'categoria', 'category_id', 'ordem', 'ativo')
                        ->where('ativo', true)
                        ->with('category:id,nome,icone,ordem,ativo')
                        ->orderBy('category_id')
                        ->orderBy('categoria')
                        ->orderBy('ordem')
                        ->orderBy('titulo');
                },
            ])
            ->first();

        if (! $profile) {
            return response()->json(['paths' => [], 'pages' => []]);
        }

        $pages = $profile->pages->values()->map(function ($page) {
            return [
                'id' => $page->id,
                'titulo' => $page->titulo,
                'path' => $page->path,
                'icone' => $page->icone,
                'categoria' => $page->categoria,
                'category_id' => $page->category_id,
                'ordem' => $page->ordem,
                'ativo' => $page->ativo,
                'category' => $page->category ? [
                    'id' => $page->category->id,
                    'nome' => $page->category->nome,
                    'icone' => $page->category->icone,
                    'ordem' => $page->category->ordem,
                    'ativo' => $page->category->ativo,
                ] : null,
            ];
        });

        return response()->json([
            'paths' => $profile->pages->pluck('path')->values(),
            'pages' => $pages,
        ]);
    }
}
