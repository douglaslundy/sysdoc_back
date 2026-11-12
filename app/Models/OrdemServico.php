<?php

namespace App\Models;

use App\Models\Scopes\CadastroProtocoloScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    public const PRIORIDADES = ['baixa', 'normal', 'alta', 'urgente'];

    public const ENCERRADAS = ['concluida', 'cancelada'];

    protected $fillable = [
        'numero', 'secretaria_id', 'tipo_servico_id', 'solicitante_id', 'tecnico_id', 'local', 'endereco', 'descricao', 'problema',
        'prioridade', 'prazo', 'latitude', 'longitude', 'contato_nome', 'contato_telefone', 'status',
        'fechamento_relato', 'materiais', 'tempo_gasto_min', 'concluida_em', 'prazo_avisado_em',
    ];

    protected $casts = ['prazo' => 'date:Y-m-d', 'concluida_em' => 'datetime', 'prazo_avisado_em' => 'datetime'];

    /** Admin vê tudo; os demais veem as que abriram, as atribuídas a eles e (gestor) as das suas secretarias. */
    public function scopeVisiveisPara($query, ?User $user)
    {
        if (! $user) {
            return $query->whereRaw('1=0');
        }
        if ($user->profile === 'admin') {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('ordens_servico.solicitante_id', $user->id)->orWhere('ordens_servico.tecnico_id', $user->id);
            if ($user->profile === 'gestor') {
                $q->orWhereIn('ordens_servico.secretaria_id', CadastroProtocoloScope::secretariasDe($user));
            }
        });
    }

    public function ehGestorDaSecretaria(?User $user): bool
    {
        return $user?->profile === 'admin'
            || ($user?->profile === 'gestor' && CadastroProtocoloScope::pode($user, $this->secretaria_id));
    }

    /** Número sequencial por ano: 2026/000123. Chamar dentro de transação. */
    public static function gerarNumero(): string
    {
        $ano = now()->format('Y');
        $ultimo = static::where('numero', 'like', "{$ano}/%")->lockForUpdate()->max('numero');
        $seq = $ultimo ? ((int) substr($ultimo, 5)) + 1 : 1;

        return sprintf('%s/%06d', $ano, $seq);
    }

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(Secretaria::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(OsTipoServico::class, 'tipo_servico_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id')->select('id', 'name', 'email', 'phone');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id')->select('id', 'name', 'email', 'phone');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(OsFoto::class);
    }

    public function historico(): HasMany
    {
        return $this->hasMany(OsHistorico::class)->orderBy('id');
    }
}
