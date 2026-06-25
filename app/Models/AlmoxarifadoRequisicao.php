<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmoxarifadoRequisicao extends Model
{
    protected $table = 'almoxarifado_requisicoes';

    protected $fillable = [
        'numero',
        'almoxarifado_secretaria_id',
        'solicitante',
        'requisitante_user_id',
        'data_solicitacao',
        'status',
        'justificativa',
        'observacoes',
        'usuario_responsavel_id',
        'data_atendimento',
        'data_entrega',
    ];

    protected $casts = [
        'data_solicitacao' => 'date',
        'data_atendimento' => 'datetime',
        'data_entrega' => 'datetime',
    ];

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(AlmoxarifadoSecretaria::class, 'almoxarifado_secretaria_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_responsavel_id');
    }

    public function requisitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requisitante_user_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(AlmoxarifadoRequisicaoItem::class, 'almoxarifado_requisicao_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(AlmoxarifadoRequisicaoHistorico::class, 'almoxarifado_requisicao_id');
    }
}
