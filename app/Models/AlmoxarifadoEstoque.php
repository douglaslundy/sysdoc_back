<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmoxarifadoEstoque extends Model
{
    protected $table = 'almoxarifado_estoques';

    protected $fillable = [
        'almoxarifado_produto_id',
        'almoxarifado_secretaria_id',
        'quantidade_disponivel',
        'quantidade_reservada',
        'quantidade_em_separacao',
        'quantidade_entregue',
    ];

    protected $casts = [
        'quantidade_disponivel' => 'decimal:3',
        'quantidade_reservada' => 'decimal:3',
        'quantidade_em_separacao' => 'decimal:3',
        'quantidade_entregue' => 'decimal:3',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoProduto::class, 'almoxarifado_produto_id');
    }

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoSecretaria::class, 'almoxarifado_secretaria_id');
    }
}
