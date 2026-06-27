<?php

namespace App\Http\Controllers;

use App\Models\DocumentConfig;
use App\Models\User;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DocumentConfigController extends Controller
{
    private const SIGILOS = ['publico', 'interno', 'restrito'];

    public function show(Request $request): JsonResponse
    {
        if (! $this->canManageConfig($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        if (! Schema::hasTable('document_configs')) {
            return response()->json($this->defaultPayload(true));
        }

        return response()->json($this->payload(DocumentConfig::current()));
    }

    public function update(Request $request): JsonResponse
    {
        if (! $this->canManageConfig($request->user())) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        if (! Schema::hasTable('document_configs')) {
            return response()->json([
                'message' => 'A configuração de documentos ainda não está disponível. Execute a migration document_configs antes de salvar.',
            ], 409);
        }

        $validated = $request->validate([
            'triple_signature_enabled' => ['nullable', 'boolean'],
            'triple_signature_sigilos' => ['nullable', 'array'],
            'triple_signature_sigilos.*' => ['string', 'distinct', 'in:publico,interno,restrito'],
            'signer_user_1_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
            'signer_user_2_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
            'signer_user_3_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $config = DocumentConfig::current();
        $enabled = $request->boolean('triple_signature_enabled');
        $signers = array_values(array_filter([
            $validated['signer_user_1_id'] ?? null,
            $validated['signer_user_2_id'] ?? null,
            $validated['signer_user_3_id'] ?? null,
        ]));

        if (count(array_unique($signers)) !== count($signers)) {
            throw ValidationException::withMessages([
                'signers' => 'Os 3 usuários da tripla assinatura devem ser distintos.',
            ]);
        }

        $sigilos = array_values(array_unique(array_filter(array_map('strval', $validated['triple_signature_sigilos'] ?? []))));

        if ($enabled && count($sigilos) === 0) {
            throw ValidationException::withMessages([
                'triple_signature_sigilos' => 'Selecione ao menos um tipo de sigilo para aplicar a tripla assinatura.',
            ]);
        }

        if ($enabled && count($signers) !== 3) {
            throw ValidationException::withMessages([
                'signers' => 'Informe exatamente 3 usuários para a tripla assinatura.',
            ]);
        }

        $config->update([
            'triple_signature_enabled' => $enabled,
            'triple_signature_sigilos' => $sigilos,
            'signer_user_1_id' => $validated['signer_user_1_id'] ?? null,
            'signer_user_2_id' => $validated['signer_user_2_id'] ?? null,
            'signer_user_3_id' => $validated['signer_user_3_id'] ?? null,
        ]);

        return response()->json($this->payload($config->fresh()));
    }

    private function payload(DocumentConfig $config): array
    {
        $signerIds = $config->signerUserIds();
        $signers = count($signerIds) > 0
            ? User::query()->whereIn('id', $signerIds)->get(['id', 'name'])
            : collect();

        return [
            'triple_signature_enabled' => (bool) $config->triple_signature_enabled,
            'triple_signature_sigilos' => array_values(array_filter((array) $config->triple_signature_sigilos, fn ($value) => in_array($value, self::SIGILOS, true))),
            'signer_user_1_id' => $config->signer_user_1_id,
            'signer_user_2_id' => $config->signer_user_2_id,
            'signer_user_3_id' => $config->signer_user_3_id,
            'signers' => $signers->sortBy(fn ($user) => array_search($user->id, $signerIds, true))->values()->all(),
            'migration_pending' => false,
        ];
    }

    private function defaultPayload(bool $migrationPending = false): array
    {
        return [
            'triple_signature_enabled' => false,
            'triple_signature_sigilos' => ['interno', 'restrito'],
            'signer_user_1_id' => null,
            'signer_user_2_id' => null,
            'signer_user_3_id' => null,
            'signers' => [],
            'migration_pending' => $migrationPending,
        ];
    }

    private function canManageConfig(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/configuracoes');
    }
}
