<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmoxarifadoRequisicaoHistorico extends Model
{
    // Herda a secretaria da requisição (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('almoxarifado_requisicao_id', 'almoxarifado_requisicoes'));
    }

    protected $table = 'almoxarifado_requisicao_status_historicos';

    protected $fillable = [
        'almoxarifado_requisicao_id',
        'status_anterior',
        'novo_status',
        'observacao',
        'user_id',
    ];

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoRequisicao::class, 'almoxarifado_requisicao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
