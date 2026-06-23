<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmoxarifadoMovimentacao extends Model
{
    protected $table = 'almoxarifado_movimentacoes';

    protected $fillable = [
        'almoxarifado_produto_id',
        'almoxarifado_secretaria_origem_id',
        'almoxarifado_secretaria_destino_id',
        'tipo',
        'quantidade',
        'saldo_anterior',
        'saldo_posterior',
        'motivo',
        'observacao',
        'documento_tipo',
        'documento_id',
        'user_id',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'saldo_anterior' => 'decimal:3',
        'saldo_posterior' => 'decimal:3',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoProduto::class, 'almoxarifado_produto_id');
    }

    public function secretariaOrigem(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoSecretaria::class, 'almoxarifado_secretaria_origem_id');
    }

    public function secretariaDestino(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoSecretaria::class, 'almoxarifado_secretaria_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
