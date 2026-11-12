<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentTypeRequest;
use App\Models\DocumentType;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        if (! $this->canViewTypes(request()->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        return response()->json(
            DocumentType::query()
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get()
        );
    }

    public function store(DocumentTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $type = DocumentType::create([
            'codigo' => $validated['codigo'],
            'nome' => $validated['nome'],
            'descricao' => $validated['descricao'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $request->boolean('ativo', true),
        ]);

        return response()->json($type, 201);
    }

    public function update(DocumentTypeRequest $request, int $id): JsonResponse
    {
        $type = DocumentType::findOrFail($id); // 404/403 já checados no FormRequest
        $validated = $request->validated();

        $type->update([
            'codigo' => $validated['codigo'] ?? $type->codigo,
            'nome' => $validated['nome'] ?? $type->nome,
            'descricao' => array_key_exists('descricao', $validated) ? $validated['descricao'] : $type->descricao,
            'ordem' => $validated['ordem'] ?? $type->ordem,
            'ativo' => $request->has('ativo') ? $request->boolean('ativo') : $type->ativo,
        ]);

        return response()->json($type->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $type = DocumentType::find($id);
        if (! $type) {
            return response()->json(['message' => 'Tipo não encontrado.'], 404);
        }

        if (! $this->canManageTypes(request()->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $type->delete();

        return response()->json(['message' => 'Tipo removido com sucesso.']);
    }

    private function canManageTypes(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/tipos');
    }

    private function canViewTypes(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccessAny($user, ['/documentos', '/documentos/tipos']);
    }
}
