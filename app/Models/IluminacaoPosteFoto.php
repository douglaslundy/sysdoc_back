<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IluminacaoPosteFoto extends Model
{

    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('poste_id', 'iluminacao_postes'));
    }
    protected $table = 'iluminacao_poste_fotos';

    protected $fillable = [
        'poste_id', 'movimentacao_id', 'momento', 'uploaded_by',
        'disk', 'path', 'original_name', 'mime_type', 'size_bytes',
    ];

    protected $attributes = ['disk' => 'private'];

    public function poste()
    {
        return $this->belongsTo(IluminacaoPoste::class, 'poste_id');
    }

    public function movimentacao()
    {
        return $this->belongsTo(IluminacaoPosteMovimentacao::class, 'movimentacao_id');
    }
}
