<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeticaoMotivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // O acesso por página (/peticao-motivos) é aplicado por config/route_permissions.php.
        return true;
    }

    public function rules(): array
    {
        $ignore = $this->route('motivo')?->id ?? $this->route('motivo');

        return [
            'nome' => ['required', 'string', 'max:120', Rule::unique('peticao_motivos', 'nome')->ignore($ignore)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'integer', 'exists:protocol_organizational_units,id'],
            'ativo' => ['sometimes', 'boolean'],
            'ordem' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
