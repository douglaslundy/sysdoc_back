<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Protocol extends Model
{
    use HasFactory;

    protected $table = 'protocols';

    protected $fillable = [
        'numero',
        'assunto',
        'descricao',
        'tipo',
        'status',
        'prioridade',
        'solicitante_tipo',
        'solicitante_nome',
        'solicitante_documento',
        'origem_unit_id',
        'destino_unit_id',
        'responsavel_atual_id',
        'criado_por_id',
        'prazo_atendimento',
        'recebido_em',
        'encaminhado_em',
        'encerrado_em',
        'reaberto_em',
        'cancelado_em',
        'justificativa_encerramento',
        'justificativa_cancelamento',
        'novo',
        'vencido',
    ];

    protected $casts = [
        'prazo_atendimento' => 'date',
        'recebido_em' => 'datetime',
        'encaminhado_em' => 'datetime',
        'encerrado_em' => 'datetime',
        'reaberto_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'novo' => 'boolean',
        'vencido' => 'boolean',
    ];

    public static function gerarNumero(): string
    {
        do {
            $numero = 'PRT-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
        } while (self::where('numero', $numero)->exists());

        return $numero;
    }

    public function origemUnit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'origem_unit_id');
    }

    public function destinoUnit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'destino_unit_id');
    }

    public function responsavelAtual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_atual_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProtocolMovement::class, 'protocol_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProtocolComment::class, 'protocol_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProtocolAttachment::class, 'protocol_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ProtocolNotification::class, 'protocol_id');
    }

    public function visualizations(): HasMany
    {
        return $this->hasMany(ProtocolView::class, 'protocol_id');
    }

    public function kanbanTask(): HasOne
    {
        return $this->hasOne(KanbanTask::class, 'protocol_id');
    }
}
