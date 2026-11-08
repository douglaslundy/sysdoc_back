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
    use \App\Models\Concerns\BelongsToSecretaria;
    use HasFactory;

    protected $table = 'protocols';

    // Protocolo de pedido de cadastro só para admin e gestor da secretaria (ver o escopo).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\CadastroProtocoloScope());
    }

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
        'secretaria_id',
        'prazo_atendimento',
        'recebido_em',
        'encaminhado_em',
        'encerrado_em',
        'reaberto_em',
        'devolvido_em',
        'cancelado_em',
        'justificativa_encerramento',
        'justificativa_devolucao',
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
        'devolvido_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'novo' => 'boolean',
        'vencido' => 'boolean',
    ];

    public static function gerarNumero(): string
    {
        do {
            $numero = 'PRT-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (self::withoutGlobalScopes()->where('numero', $numero)->exists()); // sem escopo: número escondido não se repete

        return $numero;
    }

    /**
     * Consulta das rotas de protocolo: secretaria ativa OU participação (destinatário/tramitação).
     * Só o ProtocolController usa; qualquer outra consulta de Protocol continua isolada pelo escopo.
     */
    public static function visiveis(?User $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $ativa = app(\App\Services\Secretarias\ActiveSecretaria::class);

        return static::withoutSecretaria()->where(function ($q) use ($ativa, $user) {
            if (! $ativa->inRequest() || $ativa->isAll()) {
                $q->whereRaw('1=1');
            } elseif ($ativa->id() === null) {
                $q->whereRaw('1=0');
            } else {
                $q->where('protocols.secretaria_id', $ativa->id());
                if ($user) {
                    \App\Support\ParticipacaoProtocolo::aplicar($q, (int) $user->id);
                }
            }
        });
    }

    public function isCadastro(): bool
    {
        return $this->tipo === \App\Models\Scopes\CadastroProtocoloScope::TIPO;
    }

    public function origemUnit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'origem_unit_id')->withoutGlobalScope(\App\Models\Scopes\SecretariaScope::class);
    }

    public function destinoUnit(): BelongsTo
    {
        return $this->belongsTo(ProtocolOrganizationalUnit::class, 'destino_unit_id')->withoutGlobalScope(\App\Models\Scopes\SecretariaScope::class);
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
