<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlmoxarifadoProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'codigo_interno' => ['sometimes', 'nullable', 'string', 'max:60', 'unique:almoxarifado_produtos,codigo_interno,'.$this->route('id')],
            'codigo_barras' => ['nullable', 'string', 'max:80'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'almoxarifado_categoria_id' => ['nullable', 'integer', 'exists:almoxarifado_categorias,id'],
            'almoxarifado_especie_id' => ['nullable', 'integer', 'exists:almoxarifado_especies,id'],
            'almoxarifado_unidade_medida_id' => ['nullable', 'integer', 'exists:almoxarifado_unidades_medida,id'],
            'almoxarifado_fornecedor_id' => ['nullable', 'integer', 'exists:almoxarifado_fornecedores,id'],
            'almoxarifado_localizacao_id' => ['nullable', 'integer', 'exists:almoxarifado_localizacoes,id'],
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
