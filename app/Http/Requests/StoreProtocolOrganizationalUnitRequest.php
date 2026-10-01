<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProtocolOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'tipo' => 'required|string|max:40',
            'codigo' => 'nullable|string|max:60',
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ];
    }
}
