<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IluminacaoPosteMovimentacao extends Model
{

    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('poste_id', 'iluminacao_postes'));
    }
    public $timestamps = false;

    protected $table = 'iluminacao_poste_movimentacoes';

    protected $fillable = ['poste_id', 'user_id', 'acao', 'status_anterior', 'status_novo', 'motivo', 'dados'];

    protected $casts = [
        'dados' => 'array',
        'created_at' => 'datetime',
    ];

    public function poste()
    {
        return $this->belongsTo(IluminacaoPoste::class, 'poste_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fotos()
    {
        return $this->hasMany(IluminacaoPosteFoto::class, 'movimentacao_id');
    }
}
