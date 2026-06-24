<?php

namespace App\Http\Controllers;

use App\Models\ProtocolType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProtocolTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ProtocolType::query()
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:40|unique:protocol_types,codigo',
            'nome' => 'required|string|max:120',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
        ]);

        $type = ProtocolType::create([
            ...$validated,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $validated['ativo'] ?? true,
        ]);

        return response()->json($type, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = ProtocolType::find($id);
        if (! $type) {
            return response()->json(['message' => 'Tipo de protocolo nao encontrado.'], 404);
        }

        $validated = $request->validate([
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('protocol_types', 'codigo')->ignore($type->id),
            ],
            'nome' => 'sometimes|required|string|max:120',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
        ]);

        $type->update($validated);

        return response()->json($type->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $type = ProtocolType::find($id);
        if (! $type) {
            return response()->json(['message' => 'Tipo de protocolo nao encontrado.'], 404);
        }

        $type->update(['ativo' => false]);

        return response()->json(['message' => 'Tipo de protocolo inativado com sucesso.']);
    }
}
