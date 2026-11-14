<?php

namespace App\Http\Requests;

use App\Models\AlmoxarifadoProduto;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class MovimentarAlmoxarifadoEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A secretaria é a do produto (escopo: produto de outra secretaria nem é encontrado).
        $sid = (int) (AlmoxarifadoProduto::find((int) $this->input('almoxarifado_produto_id'))?->secretaria_id ?? 0);

        return [
            'almoxarifado_produto_id' => ['required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_produtos', $sid)],
            'almoxarifado_secretaria_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)],
            'tipo' => ['required', 'in:entrada,saida,ajuste,transferencia'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'motivo' => ['required', 'string', 'max:150'],
            'observacao' => ['nullable', 'string'],
            'secretaria_destino_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)],
        ];
    }
}
