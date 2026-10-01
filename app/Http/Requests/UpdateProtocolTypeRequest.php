<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProtocolTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('protocol_types', 'codigo')->ignore($this->route('id')),
            ],
            'nome' => 'sometimes|required|string|max:120',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
        ];
    }
}
