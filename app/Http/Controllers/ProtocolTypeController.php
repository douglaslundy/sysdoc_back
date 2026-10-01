<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProtocolTypeRequest;
use App\Http\Requests\StoreProtocolTypeRequest;
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

    public function store(StoreProtocolTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $type = ProtocolType::create([
            ...$validated,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $validated['ativo'] ?? true,
        ]);

        return response()->json($type, 201);
    }

    public function update(UpdateProtocolTypeRequest $request, int $id): JsonResponse
    {
        $type = ProtocolType::find($id);
        if (! $type) {
            return response()->json(['message' => 'Tipo de protocolo não encontrado.'], 404);
        }

                $validated = $request->validated();

        $type->update($validated);

        return response()->json($type->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $type = ProtocolType::find($id);
        if (! $type) {
            return response()->json(['message' => 'Tipo de protocolo não encontrado.'], 404);
        }

        $type->update(['ativo' => false]);

        return response()->json(['message' => 'Tipo de protocolo inativado com sucesso.']);
    }
}
