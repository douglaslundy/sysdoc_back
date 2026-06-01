<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SincronizacaoCidadao extends Model
{
    protected $table = 'sincronizacoes_cidadao';

    protected $fillable = [
        'job_id', 'status', 'total_esus', 'total_esus_previsto', 'total_sysdoc',
        'preview_criados', 'preview_atualizados', 'preview_obitos', 'preview_sem_alteracao',
        'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
        'iniciado_por', 'aplicado_por', 'analisado_em', 'aplicado_em', 'erro_mensagem',
    ];

    protected $casts = [
        'analisado_em' => 'datetime',
        'aplicado_em'  => 'datetime',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(SincronizacaoItem::class, 'sincronizacao_id');
    }

    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }
}
