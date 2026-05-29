<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SincronizacaoItem extends Model
{
    public $timestamps = false;

    protected $table = 'sincronizacao_itens';

    protected $fillable = [
        'sincronizacao_id', 'acao', 'cpf', 'cns', 'nome_esus',
        'client_id', 'payload', 'aplicado', 'erro',
    ];

    protected $casts = [
        'payload'  => 'array',
        'aplicado' => 'boolean',
    ];

    public function sincronizacao(): BelongsTo
    {
        return $this->belongsTo(SincronizacaoCidadao::class, 'sincronizacao_id');
    }
}
