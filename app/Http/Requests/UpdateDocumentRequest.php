<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Rules\TextoRico;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends DocumentWriteRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['resumo']);
    }

    public function authorize(): bool
    {
        $this->ensureCanManageRecord();

        return true;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'titulo' => ['sometimes', 'required', 'string', 'max:200'],
            'resumo' => ['nullable', 'string', new TextoRico(16000)],
            'sigilo' => ['sometimes', 'required', 'string', Rule::in(self::SIGILOS)],
            'status' => ['nullable', 'string', 'max:30'],
            'arquivo' => ['nullable', 'file', 'max:51200'],
        ];
    }
}
