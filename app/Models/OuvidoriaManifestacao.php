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

    /** Dados do cidadão em claro: só saem pela rota de revelar, que grava auditoria. */
    public function dadosPessoais(): array
    {
        return ['nome' => $this->nome, 'email' => $this->email, 'phone' => $this->phone, 'cpf' => $this->cpf];
    }

    /** Mesmos campos, mascarados (ex.: CPF ***.***.***-25) para a tela de detalhe. */
    public function dadosMascarados(): array
    {
        $d = $this->dadosPessoais();
        $oculta = fn (?string $v, int $fim) => $v === null || $v === '' ? $v : str_repeat('*', max(strlen($v) - $fim, 0)).substr($v, -$fim);
        $cpf = preg_replace('/\D/', '', (string) $d['cpf']);
        $nome = trim((string) $d['nome']);
        $email = (string) $d['email'];

        return [
            'nome' => $nome === '' ? null : (str_contains($nome, ' ') ? strtok($nome, ' ').' ***' : $nome[0].'***'),
            'email' => str_contains($email, '@') ? $email[0].'***@'.substr(strrchr($email, '@'), 1) : null,
            'phone' => $oculta($d['phone'] === null ? null : preg_replace('/\D/', '', $d['phone']), 4),
            'cpf' => strlen($cpf) === 11 ? '***.***.***-'.substr($cpf, -2) : null,
        ];
    }

    public function estaFinal(): bool
    {
        return in_array($this->status, self::FINAIS, true);
    }
}
