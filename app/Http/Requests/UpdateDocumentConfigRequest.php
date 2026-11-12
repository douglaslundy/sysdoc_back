<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Schema;

class UpdateDocumentConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! ((string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/configuracoes'))) {
            return false;
        }

        // 409 (migration pendente) vem depois do 403 e antes do 422, como no controller original.
        if (! Schema::hasTable('document_configs')) {
            throw new HttpResponseException(response()->json([
                'message' => 'A configuração de documentos ainda não está disponível. Execute a migration document_configs antes de salvar.',
            ], 409));
        }

        return true;
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403));
    }

    public function rules(): array
    {
        return [
            'triple_signature_enabled' => ['nullable', 'boolean'],
            'triple_signature_sigilos' => ['nullable', 'array'],
            'triple_signature_sigilos.*' => ['string', 'distinct', 'in:publico,interno,restrito'],
            'signer_user_1_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
            'signer_user_2_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
            'signer_user_3_id' => ['nullable', 'integer', 'distinct', 'exists:users,id'],
        ];
    }
}
