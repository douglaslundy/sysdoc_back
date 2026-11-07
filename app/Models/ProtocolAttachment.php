<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolAttachment extends Model
{
    use HasFactory;

    protected $table = 'protocol_attachments';

    // Protocolo de pedido de cadastro só para admin e gestor da secretaria (ver o escopo).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\CadastroProtocoloScope('protocol_id'));
    }

    protected $fillable = [
        'protocol_id',
        'user_id',
        'nome_original',
        'caminho',
        'mime_type',
        'tamanho_bytes',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'tamanho_bytes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class, 'protocol_id');
    }
}
