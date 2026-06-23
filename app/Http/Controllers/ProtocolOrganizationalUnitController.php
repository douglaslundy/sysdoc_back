<?php

namespace App\Http\Controllers;

use App\Models\ProtocolOrganizationalUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolOrganizationalUnitController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ProtocolOrganizationalUnit::query()
                ->with('children')
                ->whereNull('parent_id')
                ->orderBy('tipo')
                ->orderBy('nome')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'tipo' => 'required|string|max:40',
            'codigo' => 'nullable|string|max:60',
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ]);

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

        $unit->update($validated);

        return response()->json($unit->fresh());
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
