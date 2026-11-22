<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSecretaria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OuvidoriaManifestacao extends Model
{
    use BelongsToSecretaria;

    protected $table = 'ouvidoria_manifestacoes';

    public const TIPOS = [
        'reclamacao' => 'Reclamação',
        'sugestao' => 'Sugestão',
        'elogio' => 'Elogio',
        'denuncia' => 'Denúncia',
        'solicitacao' => 'Solicitação',
        'informacao' => 'Pedido de informação',
    ];

    public const STATUS = ['recebida', 'em_analise', 'encaminhada', 'respondida', 'encerrada', 'arquivada'];

    public const FINAIS = ['encerrada', 'arquivada'];

    public const TRANSICOES = [
        'recebida' => ['em_analise', 'encaminhada', 'respondida', 'arquivada'],
        'em_analise' => ['encaminhada', 'respondida', 'arquivada'],
        'encaminhada' => ['em_analise', 'respondida', 'arquivada'],
        'respondida' => ['respondida', 'encerrada', 'em_analise', 'arquivada'],
        'encerrada' => [],
        'arquivada' => [],
    ];

    // Campos do cidadão e de controle são sempre definidos pelo servidor (nunca por mass assignment de requisição).
    protected $fillable = [
        'protocolo', 'senha_hash', 'secretaria_id', 'tipo', 'assunto', 'descricao', 'status', 'anonimo', 'nome',
        'email', 'phone', 'cpf', 'responsavel_id', 'resposta', 'respondida_por_id', 'respondida_em', 'prazo_em',
        'prazo_avisado_em', 'tentativas_falhas', 'bloqueado_ate',
    ];

    protected $hidden = ['senha_hash', 'tentativas_falhas', 'bloqueado_ate'];

    protected $casts = [
        'anonimo' => 'boolean',
        'cpf' => 'encrypted',
        'respondida_em' => 'datetime',
        'prazo_em' => 'datetime',
        'prazo_avisado_em' => 'datetime',
        'bloqueado_ate' => 'datetime',
    ];

    public function historico(): HasMany
    {
        return $this->hasMany(OuvidoriaHistorico::class, 'manifestacao_id')->orderBy('id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(OuvidoriaAnexo::class, 'manifestacao_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id')->select('id', 'name');
    }

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(Secretaria::class)->select('id', 'nome');
    }

    public function estaFinal(): bool
    {
        return in_array($this->status, self::FINAIS, true);
    }
}
