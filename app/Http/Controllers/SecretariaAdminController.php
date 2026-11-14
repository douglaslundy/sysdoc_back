<?php

namespace App\Http\Controllers;

use App\Http\Requests\SecretariaRequest;
use App\Models\Secretaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SecretariaAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Secretaria::with('pages:id,titulo,path')->orderBy('nome')->get()
        );
    }

    public function store(SecretariaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $secretaria = Secretaria::create([
            'nome' => $data['nome'],
            'slug' => $this->slugUnico($data['nome']),
            'ativa' => $data['ativa'] ?? true,
        ]);
        $secretaria->pages()->sync($data['page_ids'] ?? []);

        return response()->json($secretaria->load('pages:id,titulo,path'), 201);
    }

    public function update(SecretariaRequest $request, int $id): JsonResponse
    {
        $secretaria = Secretaria::findOrFail($id);
        $data = $request->validated();
        // O slug nunca muda: ele identifica a secretaria em código (ex.: Secretaria::SAUDE_SLUG).
        $secretaria->update(array_intersect_key($data, array_flip(['nome', 'ativa'])));
        if (array_key_exists('page_ids', $data)) {
            $secretaria->pages()->sync($data['page_ids']);
        }

        return response()->json($secretaria->load('pages:id,titulo,path'));
    }

    // Sem exclusão física: o histórico de usuários/dados vinculados depende da linha.
    public function destroy(int $id): JsonResponse
    {
        if (request()->user()?->profile !== 'admin') {
            abort(403);
        }
        $secretaria = Secretaria::findOrFail($id);
        $secretaria->update(['ativa' => false]);

        return response()->json($secretaria);
    }

    private function slugUnico(string $nome): string
    {
        $base = Str::slug($nome) ?: 'secretaria';
        $slug = $base;
        for ($i = 2; Secretaria::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
