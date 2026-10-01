<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProtocolOrganizationalUnitRequest;
use App\Models\ProtocolOrganizationalUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolOrganizationalUnitController extends Controller
{
    public function index(): JsonResponse
    {
        // Árvore completa (todos os níveis): carregar só `children` de 1 nível deixava
        // de fora qualquer unidade cadastrada abaixo do segundo nível.
        $byParent = ProtocolOrganizationalUnit::query()
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->groupBy(fn (ProtocolOrganizationalUnit $unit) => $unit->parent_id ?? 0);

        $attach = function ($units) use (&$attach, $byParent) {
            foreach ($units as $unit) {
                $unit->setRelation('children', $attach($byParent->get($unit->id, collect())));
            }

            return $units;
        };

        return response()->json($attach($byParent->get(0, collect()))->values());
    }

    public function store(StoreProtocolOrganizationalUnitRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            ProtocolOrganizationalUnit::create([
                ...$validated,
                'ativo' => $validated['ativo'] ?? true,
            ]),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $unit = ProtocolOrganizationalUnit::find($id);
        if (! $unit) {
            return response()->json(['message' => 'Unidade não encontrada.'], 404);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'tipo' => 'sometimes|string|max:40',
            'codigo' => 'nullable|string|max:60',
            'nome' => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ]);

        if (! empty($validated['parent_id']) && $this->isSelfOrDescendant($unit, (int) $validated['parent_id'])) {
            return response()->json(['message' => 'A unidade pai não pode ser a própria unidade ou uma de suas subunidades.'], 422);
        }

        $unit->update($validated);

        return response()->json($unit->fresh());
    }

    private function isSelfOrDescendant(ProtocolOrganizationalUnit $unit, int $candidateId): bool
    {
        $frontier = [$unit->id];
        $seen = [];

        while ($frontier) {
            if (in_array($candidateId, $frontier, true)) {
                return true;
            }
            $seen = array_merge($seen, $frontier);
            $frontier = ProtocolOrganizationalUnit::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->diff($seen)
                ->all();
        }

        return false;
    }

    public function destroy(int $id): JsonResponse
    {
        $unit = ProtocolOrganizationalUnit::find($id);
        if (! $unit) {
            return response()->json(['message' => 'Unidade não encontrada.'], 404);
        }

        $unit->update(['ativo' => false]);

        return response()->json(['message' => 'Unidade inativada com sucesso.']);
    }
}
