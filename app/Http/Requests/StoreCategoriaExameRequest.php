<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('categoria');

        return [
            'nome' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categoria_exames', 'nome')->ignore($id),
            ],
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.unique'   => 'Esta categoria já está cadastrada.',
            'nome.max'      => 'O nome não pode ter mais de :max caracteres.',
        ];
    }
}
