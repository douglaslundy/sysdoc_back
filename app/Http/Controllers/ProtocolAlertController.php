<?php

namespace App\Http\Controllers;

use App\Models\ProtocolAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolAlertController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ProtocolAlert::orderBy('nome')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'modulo' => 'required|string|max:80',
            'gatilho' => 'required|string|max:80',
            'condicoes' => 'nullable|array',
            'canais' => 'nullable|array',
            'destinatarios' => 'nullable|array',
            'template' => 'nullable|string',
            'ativo' => 'nullable|boolean',
            'frequencia' => 'nullable|string|max:60',
            'prevenir_duplicidade' => 'nullable|boolean',
        ]);

        return response()->json(
            ProtocolAlert::create([
                ...$validated,
                'ativo' => $validated['ativo'] ?? true,
                'prevenir_duplicidade' => $validated['prevenir_duplicidade'] ?? true,
            ]),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alert = ProtocolAlert::find($id);
        if (! $alert) {
            return response()->json(['message' => 'Alerta não encontrado.'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'modulo' => 'sometimes|required|string|max:80',
            'gatilho' => 'sometimes|required|string|max:80',
            'condicoes' => 'nullable|array',
            'canais' => 'nullable|array',
            'destinatarios' => 'nullable|array',
            'template' => 'nullable|string',
            'ativo' => 'nullable|boolean',
            'frequencia' => 'nullable|string|max:60',
            'prevenir_duplicidade' => 'nullable|boolean',
        ]);

        $alert->update($validated);

        return response()->json($alert->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $alert = ProtocolAlert::find($id);
        if (! $alert) {
            return response()->json(['message' => 'Alerta não encontrado.'], 404);
        }

        $alert->update(['ativo' => false]);
        return response()->json(['message' => 'Alerta inativado com sucesso.']);
    }
}
