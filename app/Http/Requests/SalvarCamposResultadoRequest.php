<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarCamposResultadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campos'                       => 'required|array|min:1',
            'campos.*.exame_campo_id'      => 'required|exists:exame_campos,id',
            'campos.*.exame_id'            => 'required|exists:exames,id',
            'campos.*.valor_numerico'      => 'nullable|numeric|max:99999999999',
            'campos.*.valor_texto'         => 'nullable|string',
            'campos.*.observacao'          => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'campos.required'                  => 'Nenhum campo informado.',
            'campos.*.exame_campo_id.required' => 'O identificador do campo é obrigatório.',
            'campos.*.exame_campo_id.exists'   => 'Campo de exame não encontrado.',
            'campos.*.exame_id.required'       => 'O identificador do exame é obrigatório.',
            'campos.*.exame_id.exists'         => 'Exame não encontrado.',
            'campos.*.valor_numerico.max'      => 'O valor informado está fora do limite permitido para este campo de exame.',
        ];
    }
}
