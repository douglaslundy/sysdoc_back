<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolAttachment extends Model
{
    use HasFactory;

    protected $table = 'protocol_attachments';

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
