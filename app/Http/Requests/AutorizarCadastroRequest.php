<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Só o formato; perfil ativo, secretarias e limites do gestor são conferidos dentro da transação do serviço. */
class AutorizarCadastroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'perfil_slug' => ['required', 'string', 'max:50'],
            'secretaria_ids' => ['required', 'array', 'min:1'],
            'secretaria_ids.*' => ['integer', 'distinct'],
        ];
    }
}
