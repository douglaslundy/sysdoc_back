<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampoReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campoId = $this->route('campo');
        $ignorId = $this->route('referencia');

        return [
            'perfil' => [
                'required',
                'in:geral,adulto_m,adulto_f,crianca,crianca_m,crianca_f,adolescente,adolescente_m,adolescente_f,idoso,idoso_m,idoso_f,gestante,gestante_t1,gestante_t2,gestante_t3,recem_nascido',
                Rule::unique('campo_referencias')->where('exame_campo_id', $campoId)->ignore($ignorId),
            ],
            'sexo' => 'nullable|in:M,F',
            'idade_min_dias' => 'nullable|integer|min:0',
            'idade_max_dias' => 'nullable|integer|min:0|gte:idade_min_dias',
            'valor_min' => 'nullable|numeric',
            'valor_max' => 'nullable|numeric|gte:valor_min',
            'valor_texto' => 'nullable|string|max:200',
            'descricao' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'perfil.required' => 'O campo perfil é obrigatório.',
            'perfil.in' => 'Perfil inválido.',
            'perfil.unique' => 'Já existe uma referência para este perfil neste campo.',
            'valor_max.gte' => 'O valor máximo deve ser maior ou igual ao valor mínimo.',
        ];
    }
}
