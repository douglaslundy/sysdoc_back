<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProtocolTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:40|unique:protocol_types,codigo',
            'nome' => 'required|string|max:120',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
        ];
    }
}
