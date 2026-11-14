<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/** Tipos de documento (store sem {id}, update com): 404 (só no update) e 403 vêm antes da validação. */
class DocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('id') !== null && ! DocumentType::find($this->route('id'))) {
            throw new HttpResponseException(response()->json(['message' => 'Tipo não encontrado.'], 404));
        }

        $user = $this->user();

        return (bool) ($user && ((string) ($user->profile ?? '') === 'admin'
            || app(PagePermissionService::class)->canAccess($user, '/documentos/tipos')));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403));
    }

    public function rules(): array
    {
        $id = $this->route('id');
        if ($id === null) {
            return [
                'codigo' => ['required', 'string', 'max:60', 'unique:document_types,codigo'],
                'nome' => ['required', 'string', 'max:150'],
                'descricao' => ['nullable', 'string'],
                'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'ativo' => ['nullable', 'boolean'],
            ];
        }

        return [
            'codigo' => ['sometimes', 'required', 'string', 'max:60', Rule::unique('document_types', 'codigo')->ignore((int) $id)],
            'nome' => ['sometimes', 'required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
