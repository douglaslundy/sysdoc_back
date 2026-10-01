<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProtocolOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:protocol_organizational_units,id',
            'tipo' => 'sometimes|string|max:40',
            'codigo' => 'nullable|string|max:60',
            'nome' => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ];
    }
}
