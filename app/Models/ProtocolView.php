<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolView extends Model
{
    use HasFactory;

    protected $table = 'protocol_views';

    // Protocolo de pedido de cadastro só para admin e gestor da secretaria (ver o escopo).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('protocol_id', 'protocols', true));
        static::addGlobalScope(new \App\Models\Scopes\CadastroProtocoloScope('protocol_id'));
    }

    protected $fillable = [
        'protocol_id',
        'user_id',
        'departamento',
        'equipe',
        'session_key',
        'visualized_at',
    ];

    protected $casts = [
        'visualized_at' => 'datetime',
    ];

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id')->withoutGlobalScope(\App\Models\Scopes\SecretariaScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
