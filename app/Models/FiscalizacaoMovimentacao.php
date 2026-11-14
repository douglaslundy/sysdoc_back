<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalizacaoMovimentacao extends Model
{

    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('fiscalizacao_id', 'fiscalizacoes'));
    }
    public $timestamps = false;

    protected $table = 'fiscalizacao_movimentacoes';

    protected $fillable = ['fiscalizacao_id', 'user_id', 'acao', 'descricao', 'dados', 'publico'];

    protected $casts = [
        'dados' => 'array',
        'publico' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function fiscalizacao()
    {
        return $this->belongsTo(Fiscalizacao::class, 'fiscalizacao_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
