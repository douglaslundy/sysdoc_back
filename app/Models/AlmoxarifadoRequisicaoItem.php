<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmoxarifadoRequisicaoItem extends Model
{
    protected $table = 'almoxarifado_requisicao_itens';

    protected $fillable = [
        'almoxarifado_requisicao_id',
        'almoxarifado_produto_id',
        'quantidade_solicitada',
        'quantidade_atendida',
        'quantidade_entregue',
        'observacao',
    ];

    protected $casts = [
        'quantidade_solicitada' => 'decimal:3',
        'quantidade_atendida' => 'decimal:3',
        'quantidade_entregue' => 'decimal:3',
    ];

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoRequisicao::class, 'almoxarifado_requisicao_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoProduto::class, 'almoxarifado_produto_id');
    }
}
