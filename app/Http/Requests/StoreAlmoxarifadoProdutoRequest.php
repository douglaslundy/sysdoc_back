<?php

namespace App\Http\Requests;

use App\Services\Secretarias\ActiveSecretaria;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlmoxarifadoProdutoRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'codigo_interno' => ['nullable', 'string', 'max:60', EscopoAlmoxarifado::unico('almoxarifado_produtos', 'codigo_interno', $sid)],
            'codigo_barras' => ['nullable', 'string', 'max:80'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'almoxarifado_categoria_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_categorias', $sid)],
            'almoxarifado_especie_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_especies', $sid)],
            'almoxarifado_unidade_medida_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_unidades_medida', $sid)],
            'almoxarifado_fornecedor_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_fornecedores', $sid)],
            'almoxarifado_localizacao_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_localizacoes', $sid)],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'fabricante' => ['nullable', 'string', 'max:120'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'lote' => ['nullable', 'string', 'max:80'],
            'validade' => ['nullable', 'date'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'estoque_maximo' => ['nullable', 'numeric', 'min:0'],
            'almoxarifado' => ['nullable', 'string', 'max:120'],
            'sala' => ['nullable', 'string', 'max:80'],
            'corredor' => ['nullable', 'string', 'max:80'],
            'estante' => ['nullable', 'string', 'max:80'],
            'prateleira' => ['nullable', 'string', 'max:80'],
            'gaveta' => ['nullable', 'string', 'max:80'],
            'caixa' => ['nullable', 'string', 'max:80'],
            'posicao' => ['nullable', 'string', 'max:80'],
            'observacao_localizacao' => ['nullable', 'string'],
            'imagem_url' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
