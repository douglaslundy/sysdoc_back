<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlvaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estabelecimento_id'  => ['sometimes', 'required', 'integer', 'exists:estabelecimentos,id'],
            'nivel_risco'         => ['sometimes', 'required', Rule::in(['1', '2', '3', 'N/A'])],
            'data_alvara'         => ['sometimes', 'required', 'date'],
            'vencimento_alvara'   => ['nullable', 'date', 'after_or_equal:data_alvara'],
            'contato'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nivel_risco.in'                   => 'O nível de risco deve ser 1 (Baixo), 2 (Médio), 3 (Alto) ou N/A.',
            'vencimento_alvara.after_or_equal'  => 'O vencimento não pode ser anterior à data do alvará.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('numero_alvara');
    }
}
