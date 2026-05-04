<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicoSolicitanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ignorId = $this->route('medico');

        return [
            'nome'          => 'required|string|max:100',
            'crm'           => [
                'nullable', 'string', 'max:20',
                Rule::unique('medicos_solicitantes', 'crm')->ignore($ignorId),
            ],
            'uf_crm'        => 'nullable|string|size:2',
            'especialidade' => 'nullable|string|max:80',
            'telefone'      => 'nullable|string|max:20',
            'ativo'         => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do médico é obrigatório.',
            'crm.unique'    => 'Este CRM já está cadastrado.',
        ];
    }
}
