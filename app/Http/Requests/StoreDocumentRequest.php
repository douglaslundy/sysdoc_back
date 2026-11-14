<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreDocumentRequest extends DocumentWriteRequest
{
    public function authorize(): bool
    {
        return $this->canManageDocuments($this->user());
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'titulo' => ['required', 'string', 'max:200'],
            'resumo' => ['nullable', 'string'],
            'sigilo' => ['required', 'string', Rule::in(self::SIGILOS)],
            'status' => ['nullable', 'string', 'max:30'],
            'arquivo' => ['required', 'file', 'max:51200'],
            'secretaria_id' => \App\Models\Secretaria::regraId(),
        ];
    }
}
