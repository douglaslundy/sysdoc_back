<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProtocolOrganizationalUnitRequest;
use App\Http\Requests\StoreProtocolOrganizationalUnitRequest;
use App\Models\ProtocolOrganizationalUnit;
use App\Services\Secretarias\ActiveSecretaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolOrganizationalUnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Árvore completa (todos os níveis): carregar só `children` de 1 nível deixava
        // de fora qualquer unidade cadastrada abaixo do segundo nível.
        // ?todas=1: árvore de todas as secretarias (só nomes), para escolher o destino de um protocolo entre secretarias.
        $byParent = ($request->boolean('todas') ? ProtocolOrganizationalUnit::withoutSecretaria() : ProtocolOrganizationalUnit::query())
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

        // Unidade pai precisa ser da mesma secretaria da nova unidade (admin em "Todas" informa secretaria_id).
        $secretariaId = app(ActiveSecretaria::class)->forCreate($validated['secretaria_id'] ?? null);
        if ($secretariaId && ! empty($validated['parent_id']) && ! $this->mesmaSecretaria((int) $validated['parent_id'], $secretariaId)) {
            return response()->json(['message' => 'A unidade pai é de outra secretaria.'], 422);
        }

        return response()->json(
            ProtocolOrganizationalUnit::create([
                ...$validated,
                'ativo' => $validated['ativo'] ?? true,
            ]),
            201
        );
    }

    public function update(UpdateProtocolOrganizationalUnitRequest $request, int $id): JsonResponse
    {
        $unit = ProtocolOrganizationalUnit::find($id);
        if (! $unit) {
            return response()->json(['message' => 'Unidade não encontrada.'], 404);
        }

                $validated = $request->validated();

        if (! empty($validated['parent_id']) && $this->isSelfOrDescendant($unit, (int) $validated['parent_id'])) {
            return response()->json(['message' => 'A unidade pai não pode ser a própria unidade ou uma de suas subunidades.'], 422);
        }

        if (! empty($validated['parent_id']) && ! $this->mesmaSecretaria((int) $validated['parent_id'], (int) $unit->secretaria_id)) {
            return response()->json(['message' => 'A unidade pai é de outra secretaria.'], 422);
        }

        // A secretaria de uma unidade nunca muda por aqui.
        $unit->update(collect($validated)->except('secretaria_id')->all());

        return response()->json($unit->fresh());
    }

    private function mesmaSecretaria(int $unitId, int $secretariaId): bool
    {
        return (int) ProtocolOrganizationalUnit::withoutSecretaria()->whereKey($unitId)->value('secretaria_id') === $secretariaId;
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
