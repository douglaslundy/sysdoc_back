<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $table = 'message_logs';

    protected $fillable = [
        'canal',
        'user_id',
        'destino',
        'assunto',
        'mensagem',
        'status',
        'erro',
        'origem',
        'protocol_id',
        'enviada_em',
    ];

    protected $casts = [
        'enviada_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
