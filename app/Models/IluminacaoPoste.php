<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IluminacaoPoste extends Model
{
    use HasFactory;

    public const PENDENTE = 'pendente';
    public const AGUARDANDO = 'aguardando';
    public const NAO_CONCLUIDO = 'nao_concluido';
    public const CONCLUIDO = 'concluido';

    protected $table = 'iluminacao_postes';

    // status, protocolo e cadastrado_por ficam de fora: só o servidor define.
    protected $fillable = [
        'client_uuid', 'ponto_referencia', 'logradouro', 'numero', 'bairro',
        'latitude', 'longitude', 'precisao_m',
    ];

    protected $attributes = [
        'status' => self::PENDENTE,
        'active' => true,
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'precisao_m' => 'decimal:2',
        'active' => 'boolean',
        'concluido_em' => 'datetime',
    ];

    public function cadastrador()
    {
        return $this->belongsTo(User::class, 'cadastrado_por');
    }

    public function fotos()
    {
        return $this->hasMany(IluminacaoPosteFoto::class, 'poste_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(IluminacaoPosteMovimentacao::class, 'poste_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('active', true);
    }

    public function scopePendentes($query)
    {
        return $query->where('status', '!=', self::CONCLUIDO);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', self::CONCLUIDO);
    }

    public function scopeNoPeriodo($query, string $de, string $ate)
    {
        return $query->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59']);
    }
}
