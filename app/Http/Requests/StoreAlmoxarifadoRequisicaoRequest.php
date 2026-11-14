<?php

namespace App\Http\Requests;

use App\Services\Secretarias\ActiveSecretaria;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlmoxarifadoRequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sid = app(ActiveSecretaria::class)->forCreate($this->input('secretaria_id')) ?? 0;

        return [
            'secretaria_id' => EscopoAlmoxarifado::regraCampo(),
            'almoxarifado_secretaria_id' => ['required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)],
            'justificativa' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.almoxarifado_produto_id' => ['required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_produtos', $sid)],
            'itens.*.quantidade_solicitada' => ['required', 'numeric', 'min:0.001'],
            'itens.*.observacao' => ['nullable', 'string'],
        ];
    }
}
