<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nome' => 'required|string|max:100',
            'codigo' => 'required|string|max:30',
            'categoria_exame_id' => 'nullable|exists:categoria_exames,id',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ];

        if ($this->method() === 'PUT') {
            $rules['codigo'] = ['required', 'string', 'max:30', Rule::unique('exames', 'codigo')->ignore($this->route('exame'))];
        } else {
            $rules['codigo'] = ['required', 'string', 'max:30', Rule::unique('exames', 'codigo')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais de :max caracteres.',
            'codigo.required' => 'O campo código é obrigatório.',
            'codigo.unique' => 'Este código já está em uso.',
            'codigo.max' => 'O campo código não pode ter mais de :max caracteres.',
        ];
    }
}
