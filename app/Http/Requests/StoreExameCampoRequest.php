<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExameCampoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:200',
            'tipo_valor' => 'required|in:numerico,texto,booleano,selecao',
            'unidade' => 'nullable|string|max:30',
            'opcoes_selecao' => 'nullable|array',
            'opcoes_selecao.*' => 'string',
            'ordem' => 'integer|min:0',
            'obrigatorio' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('tipo_valor') === 'selecao' && empty($this->input('opcoes_selecao'))) {
                $validator->errors()->add('opcoes_selecao', 'O campo opções de seleção é obrigatório quando o tipo é seleção.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'tipo_valor.required' => 'O campo tipo de valor é obrigatório.',
            'tipo_valor.in' => 'O tipo de valor deve ser: numerico, texto, booleano ou selecao.',
        ];
    }
}
