<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlvaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estabelecimento_id' => ['required', 'integer', 'exists:estabelecimentos,id'],
            'nivel_risco' => ['required', Rule::in(['1', '2', '3', 'N/A'])],
            'status' => ['sometimes', 'nullable', Rule::in([
                'Não requerido', 'Dispensado', 'Protocolado', 'Em análise', 'Em exigência',
                'Vigente', 'Vencido', 'Em renovação',
                'Suspenso', 'Cassado', 'Cancelado', 'Cancelado de ofício', 'Interditado',
            ])],
            'data_alvara' => ['required', 'date'],
            'vencimento_alvara' => ['nullable', 'date', 'after_or_equal:data_alvara'],
            'contato' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'estabelecimento_id.required' => 'Selecione um estabelecimento.',
            'estabelecimento_id.exists' => 'O estabelecimento selecionado não existe.',
            'nivel_risco.required' => 'O nível de risco é obrigatório.',
            'nivel_risco.in' => 'O nível de risco deve ser 1 (Baixo), 2 (Médio), 3 (Alto) ou N/A.',
            'data_alvara.required' => 'A data do alvará é obrigatória.',
            'data_alvara.date' => 'A data do alvará é inválida.',
            'vencimento_alvara.date' => 'A data de vencimento é inválida.',
            'vencimento_alvara.after_or_equal' => 'O vencimento não pode ser anterior à data do alvará.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Garante que numero_alvara nunca seja processado — gerado exclusivamente pelo backend
        $this->request->remove('numero_alvara');
    }
}
