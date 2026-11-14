<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\User;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Base dos documentos: permissão/404 vêm antes da validação (403/404 antes de 422), como no controller original. */
abstract class DocumentWriteRequest extends FormRequest
{
    public const SIGILOS = ['publico', 'interno', 'restrito'];

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403));
    }

    protected function canManageDocuments(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isAdmin($user)
            || app(PagePermissionService::class)->canAccess($user, '/documentos');
    }

    /** Documento inexistente ou fora do alcance do usuário responde 404. */
    protected function ensureCanManageRecord(): void
    {
        $user = $this->user();
        $document = Document::find($this->route('id'));

        $allowed = $document && $user && ($this->isAdmin($user)
            || ($this->canManageDocuments($user) && (int) $document->created_by === (int) $user->id));

        if (! $allowed) {
            throw new HttpResponseException(response()->json(['message' => 'Documento não encontrado.'], 404));
        }
    }

    private function isAdmin(?User $user): bool
    {
        return (string) ($user?->profile ?? '') === 'admin';
    }
}
