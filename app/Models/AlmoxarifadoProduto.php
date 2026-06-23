<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmoxarifadoProduto extends Model
{
    protected $table = 'almoxarifado_produtos';

    protected $fillable = [
        'nome',
        'descricao',
        'codigo_interno',
        'codigo_barras',
        'qr_code',
        'almoxarifado_categoria_id',
        'almoxarifado_especie_id',
        'almoxarifado_unidade_medida_id',
        'almoxarifado_fornecedor_id',
        'almoxarifado_localizacao_id',
        'marca',
        'modelo',
        'fabricante',
        'numero_serie',
        'lote',
        'validade',
        'estoque_minimo',
        'estoque_maximo',
        'almoxarifado',
        'sala',
        'corredor',
        'estante',
        'prateleira',
        'gaveta',
        'caixa',
        'posicao',
        'observacao_localizacao',
        'imagem_url',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'validade' => 'date',
        'estoque_minimo' => 'decimal:3',
        'estoque_maximo' => 'decimal:3',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoCategoria::class, 'almoxarifado_categoria_id');
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoEspecie::class, 'almoxarifado_especie_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoUnidadeMedida::class, 'almoxarifado_unidade_medida_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoFornecedor::class, 'almoxarifado_fornecedor_id');
    }

    public function localizacao(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoLocalizacao::class, 'almoxarifado_localizacao_id');
    }

    public function estoques(): HasMany
    {
        return $this->hasMany(AlmoxarifadoEstoque::class, 'almoxarifado_produto_id');
    }
}
