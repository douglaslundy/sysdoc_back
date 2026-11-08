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
        // Resultado de outra secretaria = 404 antes de validar; campos e exames só da secretaria do pedido.
        $resultado = \App\Models\ResultadoExame::find($this->route('resultado')) ?? abort(404, 'Resultado não encontrado.');
        $sid = (int) $resultado->pedido?->secretaria_id;

        return [
            'campos' => 'required|array|min:1',
            'campos.*.exame_campo_id' => ['required', \App\Support\EscopoAlmoxarifado::existeCampoDeExame($sid)],
            'campos.*.exame_id' => ['required', \App\Support\EscopoAlmoxarifado::existe('exames', $sid)],
            'campos.*.valor_numerico' => 'nullable|numeric|max:99999999999',
            'campos.*.valor_texto' => 'nullable|string',
            'campos.*.observacao' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'campos.required' => 'Nenhum campo informado.',
            'campos.*.exame_campo_id.required' => 'O identificador do campo é obrigatório.',
            'campos.*.exame_campo_id.exists' => 'Campo de exame não encontrado.',
            'campos.*.exame_id.required' => 'O identificador do exame é obrigatório.',
            'campos.*.exame_id.exists' => 'Exame não encontrado.',
            'campos.*.valor_numerico.max' => 'O valor informado está fora do limite permitido para este campo de exame.',
        ];
    }
}
